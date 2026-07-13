<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\HousekeepingTask;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Staff;

final class HousekeepingService
{
    public function __construct(
        private readonly HousekeepingTask $tasks = new HousekeepingTask(),
        private readonly Room $rooms = new Room(),
        private readonly Reservation $reservations = new Reservation(),
        private readonly Staff $staff = new Staff(),
    ) {
    }

    public function labelForType(string $type): string
    {
        return match ($type) {
            'checkout_clean' => 'Checkout clean',
            'daily_clean' => 'Daily clean',
            'deep_clean' => 'Deep clean',
            'inspection' => 'Inspection',
            default => ucfirst(str_replace('_', ' ', $type)),
        };
    }

    public function labelForStatus(string $status): string
    {
        return match ($status) {
            'pending' => 'Pending',
            'in_progress' => 'In progress',
            'completed' => 'Completed',
            'verified' => 'Verified',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    /** @return array{bg: string, text: string} */
    public function chipClasses(string $status): array
    {
        return match ($status) {
            'pending' => [
                'bg' => 'bg-secondary-fixed',
                'text' => 'text-on-secondary-fixed-variant',
            ],
            'in_progress' => [
                'bg' => 'bg-primary-fixed',
                'text' => 'text-on-primary-fixed-variant',
            ],
            'completed' => [
                'bg' => 'bg-surface-variant',
                'text' => 'text-on-surface-variant',
            ],
            'verified' => [
                'bg' => 'bg-primary-fixed',
                'text' => 'text-on-primary-fixed-variant',
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
    public function create(array $data): array
    {
        $roomId = (int) $data['room_id'];
        if ($this->rooms->findById($roomId) === null) {
            return ['ok' => false, 'error' => 'Room not found.'];
        }

        $type = (string) $data['task_type'];
        if (!in_array($type, HousekeepingTask::TYPES, true)) {
            return ['ok' => false, 'error' => 'Invalid task type.'];
        }

        $assignedTo = isset($data['assigned_to']) && $data['assigned_to'] !== '' && $data['assigned_to'] !== null
            ? (int) $data['assigned_to']
            : null;

        if ($assignedTo !== null && $this->staff->findById($assignedTo) === null) {
            return ['ok' => false, 'error' => 'Assignee not found.'];
        }

        $id = $this->tasks->create([
            'room_id' => $roomId,
            'assigned_to' => $assignedTo,
            'task_type' => $type,
            'status' => 'pending',
            'scheduled_for' => $data['scheduled_for'] ?? date('Y-m-d'),
            'notes' => $data['notes'] ?? null,
        ]);

        // Manual clean tasks for dirty rooms should flip inventory to cleaning when currently available
        $room = $this->rooms->findById($roomId);
        if ($room !== null && (string) $room['status'] === 'available' && $type !== 'inspection') {
            $this->rooms->setStatus($roomId, 'cleaning', null, 'Housekeeping task created');
        }

        return ['ok' => true, 'id' => $id];
    }

    /**
     * @return array{ok: true}|array{ok: false, error: string}
     */
    public function assign(int $taskId, ?int $staffId): array
    {
        $task = $this->tasks->findById($taskId);
        if ($task === null) {
            return ['ok' => false, 'error' => 'Task not found.'];
        }
        if (!in_array((string) $task['status'], HousekeepingTask::OPEN_STATUSES, true)) {
            return ['ok' => false, 'error' => 'Only open tasks can be assigned.'];
        }
        if ($staffId !== null && $this->staff->findById($staffId) === null) {
            return ['ok' => false, 'error' => 'Assignee not found.'];
        }

        $this->tasks->assign($taskId, $staffId);

        if ($staffId !== null) {
            (new NotificationService())->housekeepingAssigned(
                $taskId,
                $staffId,
                (string) ($task['room_number'] ?? ''),
                (string) $task['task_type'],
            );
        }

        return ['ok' => true];
    }

    /**
     * @return array{ok: true}|array{ok: false, error: string}
     */
    public function start(int $taskId): array
    {
        $task = $this->tasks->findById($taskId);
        if ($task === null) {
            return ['ok' => false, 'error' => 'Task not found.'];
        }
        if ((string) $task['status'] !== 'pending') {
            return ['ok' => false, 'error' => 'Only pending tasks can be started.'];
        }

        $this->tasks->markInProgress($taskId);

        $room = $this->rooms->findById((int) $task['room_id']);
        if ($room !== null && (string) $room['status'] === 'available') {
            $this->rooms->setStatus((int) $task['room_id'], 'cleaning', null, 'Housekeeping started');
        }

        return ['ok' => true];
    }

    /**
     * @return array{ok: true, room_released: bool}|array{ok: false, error: string}
     */
    public function complete(int $taskId, ?int $actorId): array
    {
        $task = $this->tasks->findById($taskId);
        if ($task === null) {
            return ['ok' => false, 'error' => 'Task not found.'];
        }
        if (!in_array((string) $task['status'], ['pending', 'in_progress'], true)) {
            return ['ok' => false, 'error' => 'Task is already finished.'];
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $this->tasks->markCompleted($taskId);
            $released = $this->maybeReleaseRoom((int) $task['room_id'], $actorId, 'Cleaning completed');
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
    public function verify(int $taskId, ?int $actorId): array
    {
        $task = $this->tasks->findById($taskId);
        if ($task === null) {
            return ['ok' => false, 'error' => 'Task not found.'];
        }
        if ((string) $task['status'] !== 'completed') {
            return ['ok' => false, 'error' => 'Only completed tasks can be verified.'];
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $this->tasks->markVerified($taskId);
            $released = $this->maybeReleaseRoom((int) $task['room_id'], $actorId, 'Cleaning verified');
            $pdo->commit();

            return ['ok' => true, 'room_released' => $released];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Set cleaning → available when safe (no in-house guest, no open maintenance, no other open HK).
     */
    private function maybeReleaseRoom(int $roomId, ?int $actorId, string $reason): bool
    {
        $room = $this->rooms->findById($roomId);
        if ($room === null || (string) $room['status'] !== 'cleaning') {
            return false;
        }

        if ($this->reservations->countActiveForRoom($roomId) > 0) {
            return false;
        }

        if ($this->hasOpenMaintenance($roomId)) {
            return false;
        }

        // Other open HK tasks on this room block release
        if ($this->tasks->pendingForRoom($roomId) !== []) {
            return false;
        }

        $this->rooms->setStatus($roomId, 'available', $actorId, $reason);

        return true;
    }

    private function hasOpenMaintenance(int $roomId): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*) FROM maintenance_requests
             WHERE room_id = :room_id
               AND status IN (\'open\', \'in_progress\')'
        );
        $stmt->execute(['room_id' => $roomId]);

        return (int) $stmt->fetchColumn() > 0;
    }
}
