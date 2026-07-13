<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\HousekeepingTask;
use App\Models\MaintenanceRequest;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Staff;

final class MaintenanceService
{
    public function __construct(
        private readonly MaintenanceRequest $requests = new MaintenanceRequest(),
        private readonly Room $rooms = new Room(),
        private readonly Staff $staff = new Staff(),
        private readonly Reservation $reservations = new Reservation(),
        private readonly HousekeepingTask $housekeeping = new HousekeepingTask(),
    ) {
    }

    public function labelForStatus(string $status): string
    {
        return match ($status) {
            'open' => 'Open',
            'in_progress' => 'In progress',
            'resolved' => 'Resolved',
            'cancelled' => 'Cancelled',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    public function labelForPriority(string $priority): string
    {
        return match ($priority) {
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
            'urgent' => 'Urgent',
            default => ucfirst($priority),
        };
    }

    /** @return array{bg: string, text: string} */
    public function statusChipClasses(string $status): array
    {
        return match ($status) {
            'open' => [
                'bg' => 'bg-secondary-fixed',
                'text' => 'text-on-secondary-fixed-variant',
            ],
            'in_progress' => [
                'bg' => 'bg-primary-fixed',
                'text' => 'text-on-primary-fixed-variant',
            ],
            'resolved' => [
                'bg' => 'bg-surface-variant',
                'text' => 'text-on-surface-variant',
            ],
            'cancelled' => [
                'bg' => 'bg-error-container',
                'text' => 'text-on-error-container',
            ],
            default => [
                'bg' => 'bg-surface-variant',
                'text' => 'text-on-surface-variant',
            ],
        };
    }

    /** @return array{bg: string, text: string} */
    public function priorityChipClasses(string $priority): array
    {
        return match ($priority) {
            'low' => [
                'bg' => 'bg-surface-variant',
                'text' => 'text-on-surface-variant',
            ],
            'medium' => [
                'bg' => 'bg-primary-fixed',
                'text' => 'text-on-primary-fixed-variant',
            ],
            'high' => [
                'bg' => 'bg-secondary-fixed',
                'text' => 'text-on-secondary-fixed-variant',
            ],
            'urgent' => [
                'bg' => 'bg-error-container',
                'text' => 'text-on-error-container',
            ],
            default => [
                'bg' => 'bg-surface-variant',
                'text' => 'text-on-surface-variant',
            ],
        };
    }

    /**
     * @param array<string, mixed> $data
     * @return array{ok: true, id: int}|array{ok: false, error: string}
     */
    public function create(array $data, ?int $reportedBy): array
    {
        $priority = (string) $data['priority'];
        if (!in_array($priority, MaintenanceRequest::PRIORITIES, true)) {
            return ['ok' => false, 'error' => 'Select a valid priority.'];
        }

        $roomId = isset($data['room_id']) && $data['room_id'] !== '' && $data['room_id'] !== null
            ? (int) $data['room_id']
            : null;

        if ($roomId !== null) {
            $room = $this->rooms->findById($roomId);
            if ($room === null) {
                return ['ok' => false, 'error' => 'Room not found.'];
            }
        }

        $assignedTo = isset($data['assigned_to']) && $data['assigned_to'] !== '' && $data['assigned_to'] !== null
            ? (int) $data['assigned_to']
            : null;
        if ($assignedTo !== null && $this->staff->findById($assignedTo) === null) {
            return ['ok' => false, 'error' => 'Assignee not found.'];
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $id = $this->requests->create([
                'room_id' => $roomId,
                'reported_by' => $reportedBy,
                'assigned_to' => $assignedTo,
                'issue_title' => trim((string) $data['issue_title']),
                'description' => $data['description'] ?? null,
                'priority' => $priority,
                'status' => 'open',
            ]);

            if ($roomId !== null) {
                $this->takeRoomOffline($roomId, $reportedBy, 'Maintenance request opened');
            }

            $pdo->commit();

            (new NotificationService())->maintenanceOpened(
                $id,
                trim((string) $data['issue_title']),
                $priority,
                $assignedTo,
                $reportedBy,
            );

            return ['ok' => true, 'id' => $id];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * @return array{ok: true}|array{ok: false, error: string}
     */
    public function assign(int $id, ?int $staffId): array
    {
        $request = $this->requests->findById($id);
        if ($request === null) {
            return ['ok' => false, 'error' => 'Request not found.'];
        }
        if (!in_array((string) $request['status'], MaintenanceRequest::OPEN_STATUSES, true)) {
            return ['ok' => false, 'error' => 'Only open requests can be assigned.'];
        }
        if ($staffId !== null && $this->staff->findById($staffId) === null) {
            return ['ok' => false, 'error' => 'Assignee not found.'];
        }

        $this->requests->assign($id, $staffId);

        if ($staffId !== null) {
            (new NotificationService())->maintenanceAssigned(
                $id,
                $staffId,
                (string) $request['issue_title'],
            );
        }

        return ['ok' => true];
    }

    /**
     * @return array{ok: true}|array{ok: false, error: string}
     */
    public function start(int $id, ?int $actorId): array
    {
        $request = $this->requests->findById($id);
        if ($request === null) {
            return ['ok' => false, 'error' => 'Request not found.'];
        }
        if ((string) $request['status'] !== 'open') {
            return ['ok' => false, 'error' => 'Only open requests can be started.'];
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $this->requests->markInProgress($id);
            if ($request['room_id'] !== null) {
                $this->takeRoomOffline((int) $request['room_id'], $actorId, 'Maintenance in progress');
            }
            $pdo->commit();

            return ['ok' => true];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * @return array{ok: true, room_released: bool}|array{ok: false, error: string}
     */
    public function resolve(int $id, ?int $actorId): array
    {
        $request = $this->requests->findById($id);
        if ($request === null) {
            return ['ok' => false, 'error' => 'Request not found.'];
        }
        if (!in_array((string) $request['status'], MaintenanceRequest::OPEN_STATUSES, true)) {
            return ['ok' => false, 'error' => 'Request is already closed.'];
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $this->requests->markResolved($id);
            $released = false;
            if ($request['room_id'] !== null) {
                $released = $this->maybeReleaseRoom((int) $request['room_id'], $id, $actorId, 'Maintenance resolved');
            }
            $pdo->commit();

            return ['ok' => true, 'room_released' => $released];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * @return array{ok: true, room_released: bool}|array{ok: false, error: string}
     */
    public function cancel(int $id, ?int $actorId): array
    {
        $request = $this->requests->findById($id);
        if ($request === null) {
            return ['ok' => false, 'error' => 'Request not found.'];
        }
        if (!in_array((string) $request['status'], MaintenanceRequest::OPEN_STATUSES, true)) {
            return ['ok' => false, 'error' => 'Request is already closed.'];
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $this->requests->markCancelled($id);
            $released = false;
            if ($request['room_id'] !== null) {
                $released = $this->maybeReleaseRoom((int) $request['room_id'], $id, $actorId, 'Maintenance cancelled');
            }
            $pdo->commit();

            return ['ok' => true, 'room_released' => $released];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private function takeRoomOffline(int $roomId, ?int $actorId, string $reason): void
    {
        $room = $this->rooms->findById($roomId);
        if ($room === null) {
            return;
        }

        // Do not displace an in-house guest; Front Desk must move them first.
        if ((string) $room['status'] === 'occupied') {
            return;
        }

        if ((string) $room['status'] !== 'maintenance') {
            $this->rooms->setStatus($roomId, 'maintenance', $actorId, $reason);
        }
    }

    private function maybeReleaseRoom(int $roomId, int $exceptRequestId, ?int $actorId, string $reason): bool
    {
        $room = $this->rooms->findById($roomId);
        if ($room === null || (string) $room['status'] !== 'maintenance') {
            return false;
        }

        if ($this->requests->countOpenForRoom($roomId, $exceptRequestId) > 0) {
            return false;
        }

        if ($this->reservations->countActiveForRoom($roomId) > 0) {
            return false;
        }

        // If HK still has open cleans, hand back to cleaning instead of sellable.
        if ($this->housekeeping->pendingForRoom($roomId) !== []) {
            $this->rooms->setStatus($roomId, 'cleaning', $actorId, $reason . ' — HK pending');
            return true;
        }

        $this->rooms->setStatus($roomId, 'available', $actorId, $reason);

        return true;
    }
}
