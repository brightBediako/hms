<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\HousekeepingTask;
use App\Models\Reservation;
use App\Models\ReservationTransfer;
use App\Models\Room;

/**
 * Front desk stay lifecycle: check-in, check-out, assign, transfer, extend.
 */
final class FrontDeskService
{
    public function __construct(
        private readonly Reservation $reservations = new Reservation(),
        private readonly ReservationTransfer $transfers = new ReservationTransfer(),
        private readonly HousekeepingTask $housekeeping = new HousekeepingTask(),
        private readonly AvailabilityService $availability = new AvailabilityService(),
        private readonly Room $rooms = new Room(),
    ) {
    }

    /**
     * @return array{ok: true}|array{ok: false, error: string}
     */
    public function checkIn(int $reservationId, ?int $roomId, ?int $staffId): array
    {
        $res = $this->reservations->findById($reservationId);
        if ($res === null) {
            return ['ok' => false, 'error' => 'Reservation not found.'];
        }
        if ((string) $res['status'] !== 'booked') {
            return ['ok' => false, 'error' => 'Only booked reservations can be checked in.'];
        }

        $targetRoomId = $roomId ?? (int) $res['room_id'];
        $room = $this->rooms->findById($targetRoomId);
        if ($room === null) {
            return ['ok' => false, 'error' => 'Assigned room not found.'];
        }
        if ((string) $room['status'] === 'maintenance') {
            return ['ok' => false, 'error' => 'Cannot check in to a room under maintenance.'];
        }
        if ((string) $room['status'] === 'occupied' && $targetRoomId !== (int) $res['room_id']) {
            return ['ok' => false, 'error' => 'That room is currently occupied.'];
        }

        $conflicts = $this->availability->conflicts(
            $targetRoomId,
            (string) $res['check_in_date'],
            (string) $res['check_out_date'],
            $reservationId
        );
        if ($conflicts !== []) {
            return ['ok' => false, 'error' => 'Room has a conflicting booking for these dates.'];
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $oldRoomId = (int) $res['room_id'];
            $this->reservations->markCheckedIn($reservationId, $targetRoomId);

            if ($oldRoomId !== $targetRoomId
                && $this->reservations->countActiveForRoom($oldRoomId, $reservationId) === 0
            ) {
                $old = $this->rooms->findById($oldRoomId);
                if ($old !== null && (string) $old['status'] === 'reserved') {
                    $this->rooms->setStatus($oldRoomId, 'available', $staffId, 'Room reassigned at check-in');
                }
            }

            $this->rooms->setStatus($targetRoomId, 'occupied', $staffId, 'Guest checked in');
            $pdo->commit();

            (new NotificationService())->guestCheckedIn(
                $reservationId,
                (string) $res['booking_reference'],
                (string) ($res['guest_name'] ?? 'Guest'),
                $staffId,
            );

            return ['ok' => true];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * @return array{ok: true}|array{ok: false, error: string}
     */
    public function checkOut(int $reservationId, ?int $staffId): array
    {
        $res = $this->reservations->findById($reservationId);
        if ($res === null) {
            return ['ok' => false, 'error' => 'Reservation not found.'];
        }
        if ((string) $res['status'] !== 'checked_in') {
            return ['ok' => false, 'error' => 'Only in-house guests can be checked out.'];
        }

        $roomId = (int) $res['room_id'];
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $this->reservations->markCheckedOut($reservationId);
            $this->rooms->setStatus($roomId, 'cleaning', $staffId, 'Guest checked out');
            $hkTaskId = $this->housekeeping->createCheckoutClean(
                $roomId,
                'Checkout clean for ' . (string) $res['booking_reference'],
                date('Y-m-d')
            );
            $pdo->commit();

            (new NotificationService())->guestCheckedOut(
                $reservationId,
                (string) $res['booking_reference'],
                (string) ($res['guest_name'] ?? 'Guest'),
                is_int($hkTaskId) ? $hkTaskId : null,
                $staffId,
            );

            return ['ok' => true];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Assign / change room while still booked (pre-arrival).
     *
     * @return array{ok: true}|array{ok: false, error: string}
     */
    public function assignRoom(int $reservationId, int $newRoomId, ?int $staffId): array
    {
        $res = $this->reservations->findById($reservationId);
        if ($res === null) {
            return ['ok' => false, 'error' => 'Reservation not found.'];
        }
        if ((string) $res['status'] !== 'booked') {
            return ['ok' => false, 'error' => 'Use room transfer for in-house guests.'];
        }

        $oldRoomId = (int) $res['room_id'];
        if ($oldRoomId === $newRoomId) {
            return ['ok' => true];
        }

        $room = $this->rooms->findById($newRoomId);
        if ($room === null) {
            return ['ok' => false, 'error' => 'Room not found.'];
        }
        if ((string) $room['status'] === 'maintenance') {
            return ['ok' => false, 'error' => 'Cannot assign a room under maintenance.'];
        }

        if (!$this->availability->isAvailable(
            $newRoomId,
            (string) $res['check_in_date'],
            (string) $res['check_out_date'],
            $reservationId
        )) {
            return ['ok' => false, 'error' => 'That room is not available for the stay dates.'];
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $this->reservations->assignRoom($reservationId, $newRoomId);

            if ($this->reservations->countActiveForRoom($oldRoomId, $reservationId) === 0
                && (string) ($this->rooms->findById($oldRoomId)['status'] ?? '') === 'reserved'
            ) {
                $this->rooms->setStatus($oldRoomId, 'available', $staffId, 'Reservation reassigned');
            }

            if ((string) $room['status'] === 'available') {
                $this->rooms->setStatus($newRoomId, 'reserved', $staffId, 'Assigned to arrival');
            }

            $pdo->commit();

            return ['ok' => true];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Move an in-house guest to another room.
     *
     * @return array{ok: true}|array{ok: false, error: string}
     */
    public function transfer(int $reservationId, int $toRoomId, ?string $reason, ?int $staffId): array
    {
        $res = $this->reservations->findById($reservationId);
        if ($res === null) {
            return ['ok' => false, 'error' => 'Reservation not found.'];
        }
        if ((string) $res['status'] !== 'checked_in') {
            return ['ok' => false, 'error' => 'Only in-house guests can be transferred.'];
        }

        $fromRoomId = (int) $res['room_id'];
        if ($fromRoomId === $toRoomId) {
            return ['ok' => false, 'error' => 'Choose a different room.'];
        }

        $toRoom = $this->rooms->findById($toRoomId);
        if ($toRoom === null) {
            return ['ok' => false, 'error' => 'Target room not found.'];
        }
        if (!in_array((string) $toRoom['status'], ['available', 'cleaning', 'reserved'], true)) {
            return ['ok' => false, 'error' => 'Target room is not ready for transfer.'];
        }

        if (!$this->availability->isAvailable(
            $toRoomId,
            (string) $res['check_in_date'],
            (string) $res['check_out_date'],
            $reservationId
        )) {
            return ['ok' => false, 'error' => 'Target room has a conflicting booking.'];
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $this->transfers->create(
                $reservationId,
                $fromRoomId,
                $toRoomId,
                $staffId,
                $reason !== null && trim($reason) !== '' ? trim($reason) : null
            );
            $this->reservations->assignRoom($reservationId, $toRoomId);
            $this->rooms->setStatus($fromRoomId, 'cleaning', $staffId, 'Guest transferred out');
            $this->housekeeping->createCheckoutClean(
                $fromRoomId,
                'Transfer clean — vacated during stay ' . (string) $res['booking_reference']
            );
            $this->rooms->setStatus($toRoomId, 'occupied', $staffId, 'Guest transferred in');
            $pdo->commit();

            return ['ok' => true];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Extend check-out date for an in-house stay.
     *
     * @return array{ok: true}|array{ok: false, error: string}
     */
    public function extendStay(int $reservationId, string $newCheckOut, ?int $staffId): array
    {
        $res = $this->reservations->findById($reservationId);
        if ($res === null) {
            return ['ok' => false, 'error' => 'Reservation not found.'];
        }
        if (!in_array((string) $res['status'], ['booked', 'checked_in'], true)) {
            return ['ok' => false, 'error' => 'This reservation cannot be extended.'];
        }

        $oldCheckOut = (string) $res['check_out_date'];
        if ($newCheckOut <= $oldCheckOut) {
            return ['ok' => false, 'error' => 'New check-out must be after the current check-out date.'];
        }

        // Only the extension window needs to be free on this room
        if (!$this->availability->isAvailable(
            (int) $res['room_id'],
            $oldCheckOut,
            $newCheckOut,
            $reservationId
        )) {
            return ['ok' => false, 'error' => 'Room is not available for the extended dates.'];
        }

        $this->reservations->extendStay($reservationId, $newCheckOut);

        return ['ok' => true];
    }

    /**
     * Rooms suitable for assign/transfer given stay dates.
     *
     * @return list<array<string, mixed>>
     */
    public function candidateRooms(string $checkIn, string $checkOut, ?int $exceptReservationId = null): array
    {
        return $this->availability->availableRooms($checkIn, $checkOut, $exceptReservationId);
    }
}
