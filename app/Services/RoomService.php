<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Room;

/**
 * Room status helpers. Manual updates are logged to room_status_log.
 * Later Front Desk / Housekeeping / Maintenance should drive status through
 * this service so the inventory stays consistent.
 */
final class RoomService
{
    public function __construct(private readonly Room $rooms = new Room())
    {
    }

    public function labelForStatus(string $status): string
    {
        return match ($status) {
            'available' => 'Available',
            'occupied' => 'Occupied',
            'reserved' => 'Reserved',
            'cleaning' => 'Cleaning',
            'maintenance' => 'Maintenance',
            default => ucfirst($status),
        };
    }

    /** @return array{bg: string, text: string} */
    public function chipClasses(string $status): array
    {
        return match ($status) {
            'available' => [
                'bg' => 'bg-primary-fixed',
                'text' => 'text-on-primary-fixed-variant',
            ],
            'reserved' => [
                'bg' => 'bg-secondary-fixed',
                'text' => 'text-on-secondary-fixed-variant',
            ],
            'occupied' => [
                'bg' => 'bg-tertiary-fixed',
                'text' => 'text-on-tertiary-fixed-variant',
            ],
            'cleaning' => [
                'bg' => 'bg-surface-variant',
                'text' => 'text-on-surface-variant',
            ],
            'maintenance' => [
                'bg' => 'bg-error-container',
                'text' => 'text-on-error-container',
            ],
            default => [
                'bg' => 'bg-surface-variant',
                'text' => 'text-on-surface-variant',
            ],
        };
    }

    public function occupancyPercent(array $statusCounts): int
    {
        $total = array_sum($statusCounts);
        if ($total === 0) {
            return 0;
        }

        $busy = ($statusCounts['occupied'] ?? 0) + ($statusCounts['reserved'] ?? 0);

        return (int) round(($busy / $total) * 100);
    }
}
