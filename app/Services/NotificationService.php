<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Notification;

final class NotificationService
{
    public const TYPE_RESERVATION = 'reservation';
    public const TYPE_HOUSEKEEPING = 'housekeeping';
    public const TYPE_MAINTENANCE = 'maintenance';
    public const TYPE_PAYMENT = 'payment';
    public const TYPE_SYSTEM = 'system';

    public function __construct(
        private readonly Notification $notifications = new Notification(),
    ) {
    }

    public function unreadCount(int $staffId): int
    {
        return $this->notifications->unreadCount($staffId);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForStaff(int $staffId, ?string $filter = null): array
    {
        $unreadOnly = match ($filter) {
            'unread' => true,
            'read' => false,
            default => null,
        };

        return $this->notifications->forStaff($staffId, $unreadOnly);
    }

    public function markRead(int $id, int $staffId): bool
    {
        return $this->notifications->markRead($id, $staffId);
    }

    public function markAllRead(int $staffId): int
    {
        return $this->notifications->markAllRead($staffId);
    }

    public function labelForType(?string $type): string
    {
        return match ($type) {
            self::TYPE_RESERVATION => 'Reservation',
            self::TYPE_HOUSEKEEPING => 'Housekeeping',
            self::TYPE_MAINTENANCE => 'Maintenance',
            self::TYPE_PAYMENT => 'Payment',
            self::TYPE_SYSTEM => 'System',
            default => $type ? ucfirst($type) : 'General',
        };
    }

    public function linkFor(array $row): ?string
    {
        $table = (string) ($row['related_table'] ?? '');
        $id = (int) ($row['related_id'] ?? 0);
        if ($id <= 0) {
            return null;
        }

        return match ($table) {
            'reservations' => '/reservations/' . $id,
            'housekeeping_tasks' => '/housekeeping/' . $id,
            'maintenance_requests' => '/maintenance/' . $id,
            'payments' => '/payments',
            'invoices' => '/billing/' . $id,
            default => null,
        };
    }

    /**
     * Notify one staff member. Failures are swallowed so callers stay resilient.
     *
     * @return array{ok: true, id: int}|array{ok: false, error: string}
     */
    public function notifyStaff(
        int $staffId,
        string $title,
        string $message,
        string $type = self::TYPE_SYSTEM,
        ?string $relatedTable = null,
        ?int $relatedId = null,
    ): array {
        try {
            $id = $this->notifications->create([
                'staff_id' => $staffId,
                'title' => substr(trim($title), 0, 150),
                'message' => substr(trim($message), 0, 500),
                'type' => $type,
                'related_table' => $relatedTable,
                'related_id' => $relatedId,
            ]);

            return ['ok' => true, 'id' => $id];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Fan out to all active staff with a permission key.
     *
     * @return int Number of notifications created
     */
    public function notifyByPermission(
        string $permissionKey,
        string $title,
        string $message,
        string $type = self::TYPE_SYSTEM,
        ?string $relatedTable = null,
        ?int $relatedId = null,
        ?int $exceptStaffId = null,
    ): int {
        $created = 0;
        foreach ($this->notifications->activeStaffIdsWithPermission($permissionKey) as $staffId) {
            if ($exceptStaffId !== null && $staffId === $exceptStaffId) {
                continue;
            }
            $result = $this->notifyStaff($staffId, $title, $message, $type, $relatedTable, $relatedId);
            if ($result['ok']) {
                $created++;
            }
        }

        return $created;
    }

    public function reservationCreated(
        int $reservationId,
        string $reference,
        string $guestName,
        string $roomNumber,
        ?int $actorId,
    ): void {
        $this->notifyByPermission(
            \Permission::RESERVATIONS_VIEW,
            'New reservation',
            sprintf('%s · Room #%s · %s', $reference, $roomNumber, $guestName),
            self::TYPE_RESERVATION,
            'reservations',
            $reservationId,
            $actorId,
        );
    }

    public function guestCheckedIn(int $reservationId, string $reference, string $guestName, ?int $actorId): void
    {
        $this->notifyByPermission(
            \Permission::FRONTDESK_CHECKIN,
            'Guest checked in',
            sprintf('%s · %s', $reference, $guestName),
            self::TYPE_RESERVATION,
            'reservations',
            $reservationId,
            $actorId,
        );
    }

    public function guestCheckedOut(int $reservationId, string $reference, string $guestName, ?int $housekeepingTaskId, ?int $actorId): void
    {
        $this->notifyByPermission(
            \Permission::HOUSEKEEPING_VIEW,
            'Checkout clean needed',
            sprintf('%s checked out · %s', $reference, $guestName),
            self::TYPE_HOUSEKEEPING,
            $housekeepingTaskId !== null ? 'housekeeping_tasks' : 'reservations',
            $housekeepingTaskId ?? $reservationId,
            $actorId,
        );
    }

    public function housekeepingAssigned(int $taskId, int $assigneeId, string $roomNumber, string $taskType): void
    {
        $this->notifyStaff(
            $assigneeId,
            'Housekeeping task assigned',
            sprintf('Room #%s · %s', $roomNumber, str_replace('_', ' ', $taskType)),
            self::TYPE_HOUSEKEEPING,
            'housekeeping_tasks',
            $taskId,
        );
    }

    public function maintenanceOpened(
        int $requestId,
        string $title,
        string $priority,
        ?int $assigneeId,
        ?int $actorId,
    ): void {
        $this->notifyByPermission(
            \Permission::MAINTENANCE_VIEW,
            'Maintenance request',
            sprintf('%s · Priority: %s', $title, $priority),
            self::TYPE_MAINTENANCE,
            'maintenance_requests',
            $requestId,
            $actorId,
        );

        if ($assigneeId !== null) {
            $this->notifyStaff(
                $assigneeId,
                'Maintenance assigned to you',
                sprintf('%s · Priority: %s', $title, $priority),
                self::TYPE_MAINTENANCE,
                'maintenance_requests',
                $requestId,
            );
        }
    }

    public function maintenanceAssigned(int $requestId, int $assigneeId, string $title): void
    {
        $this->notifyStaff(
            $assigneeId,
            'Maintenance assigned to you',
            $title,
            self::TYPE_MAINTENANCE,
            'maintenance_requests',
            $requestId,
        );
    }

    public function paymentRecorded(int $paymentId, int $invoiceId, string $invoiceNumber, string $amountLabel, ?int $actorId): void
    {
        $this->notifyByPermission(
            \Permission::BILLING_VIEW,
            'Payment recorded',
            sprintf('%s · %s', $invoiceNumber, $amountLabel),
            self::TYPE_PAYMENT,
            'invoices',
            $invoiceId,
            $actorId,
        );
    }
}
