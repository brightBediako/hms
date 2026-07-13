<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Reservation;
use App\Models\Room;

/**
 * Room date-range availability — blocks overlapping booked/checked_in stays.
 */
final class AvailabilityService
{
    public function __construct(
        private readonly Reservation $reservations = new Reservation(),
        private readonly Room $rooms = new Room(),
    ) {
    }

    /**
     * @return list<array<string, mixed>> Conflicting reservations (empty = available)
     */
    public function conflicts(int $roomId, string $checkIn, string $checkOut, ?int $exceptReservationId = null): array
    {
        return $this->reservations->overlapping($roomId, $checkIn, $checkOut, $exceptReservationId);
    }

    public function isAvailable(int $roomId, string $checkIn, string $checkOut, ?int $exceptReservationId = null): bool
    {
        return $this->conflicts($roomId, $checkIn, $checkOut, $exceptReservationId) === [];
    }

    /**
     * Rooms that can be sold for the stay (not maintenance; no date conflict).
     * Cleaning rooms are included for advance bookings; front desk may still assign later.
     *
     * @return list<array<string, mixed>>
     */
    public function availableRooms(string $checkIn, string $checkOut, ?int $exceptReservationId = null): array
    {
        $candidates = $this->rooms->filtered([
            'statuses' => ['available', 'reserved', 'occupied', 'cleaning'],
        ]);

        $out = [];
        foreach ($candidates as $room) {
            $roomId = (int) $room['id'];
            if ($this->isAvailable($roomId, $checkIn, $checkOut, $exceptReservationId)) {
                $out[] = $room;
            }
        }

        return $out;
    }
}
