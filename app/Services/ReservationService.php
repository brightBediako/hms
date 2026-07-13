<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Room;

final class ReservationService
{
    public function __construct(
        private readonly Reservation $reservations = new Reservation(),
        private readonly AvailabilityService $availability = new AvailabilityService(),
        private readonly Room $rooms = new Room(),
        private readonly Guest $guests = new Guest(),
    ) {
    }

    public function labelForStatus(string $status): string
    {
        return match ($status) {
            'booked' => 'Booked',
            'checked_in' => 'Checked in',
            'checked_out' => 'Checked out',
            'cancelled' => 'Cancelled',
            'no_show' => 'No show',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    public function labelForSource(string $source): string
    {
        return match ($source) {
            'walk_in' => 'Walk-in',
            'phone' => 'Phone',
            'advance' => 'Advance',
            'other' => 'Other',
            default => ucfirst($source),
        };
    }

    /** @return array{bg: string, text: string} */
    public function chipClasses(string $status): array
    {
        return match ($status) {
            'booked' => [
                'bg' => 'bg-secondary-fixed',
                'text' => 'text-on-secondary-fixed-variant',
            ],
            'checked_in' => [
                'bg' => 'bg-primary-fixed',
                'text' => 'text-on-primary-fixed-variant',
            ],
            'checked_out' => [
                'bg' => 'bg-surface-variant',
                'text' => 'text-on-surface-variant',
            ],
            'cancelled', 'no_show' => [
                'bg' => 'bg-error-container',
                'text' => 'text-on-error-container',
            ],
            default => [
                'bg' => 'bg-surface-variant',
                'text' => 'text-on-surface-variant',
            ],
        };
    }

    public function generateReference(): string
    {
        $year = (int) date('Y');
        $seq = $this->reservations->nextReferenceNumber($year);

        return sprintf('HMS-%d-%06d', $year, $seq);
    }

    /**
     * @param array<string, mixed> $data
     * @return array{ok: true, id: int}|array{ok: false, error: string, conflicts?: list<array<string, mixed>>}
     */
    public function create(array $data, ?int $staffId): array
    {
        $check = $this->validateStay($data);
        if ($check !== null) {
            return $check;
        }

        $conflicts = $this->availability->conflicts(
            (int) $data['room_id'],
            (string) $data['check_in_date'],
            (string) $data['check_out_date']
        );
        if ($conflicts !== []) {
            return [
                'ok' => false,
                'error' => 'That room is not available for the selected dates.',
                'conflicts' => $conflicts,
            ];
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $id = $this->reservations->create([
                'booking_reference' => $this->generateReference(),
                'guest_id' => (int) $data['guest_id'],
                'room_id' => (int) $data['room_id'],
                'booked_by' => $staffId,
                'source' => (string) $data['source'],
                'check_in_date' => (string) $data['check_in_date'],
                'check_out_date' => (string) $data['check_out_date'],
                'adults' => (int) $data['adults'],
                'children' => (int) $data['children'],
                'agreed_rate' => $data['agreed_rate'],
                'status' => 'booked',
                'notes' => $data['notes'],
            ]);

            $this->syncRoomStatusAfterBooking((int) $data['room_id'], $staffId);
            $pdo->commit();

            return ['ok' => true, 'id' => $id];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return array{ok: true}|array{ok: false, error: string, conflicts?: list<array<string, mixed>>}
     */
    public function update(int $id, array $data, ?int $staffId): array
    {
        $existing = $this->reservations->findById($id);
        if ($existing === null) {
            return ['ok' => false, 'error' => 'Reservation not found.'];
        }

        if (!in_array((string) $existing['status'], ['booked'], true)) {
            return ['ok' => false, 'error' => 'Only booked reservations can be modified here. Use Front Desk for in-house stays.'];
        }

        $check = $this->validateStay($data);
        if ($check !== null) {
            return $check;
        }

        $newRoomId = (int) $data['room_id'];
        $oldRoomId = (int) $existing['room_id'];

        $conflicts = $this->availability->conflicts(
            $newRoomId,
            (string) $data['check_in_date'],
            (string) $data['check_out_date'],
            $id
        );
        if ($conflicts !== []) {
            return [
                'ok' => false,
                'error' => 'That room is not available for the selected dates.',
                'conflicts' => $conflicts,
            ];
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $this->reservations->update($id, [
                'guest_id' => (int) $data['guest_id'],
                'room_id' => $newRoomId,
                'source' => (string) $data['source'],
                'check_in_date' => (string) $data['check_in_date'],
                'check_out_date' => (string) $data['check_out_date'],
                'adults' => (int) $data['adults'],
                'children' => (int) $data['children'],
                'agreed_rate' => $data['agreed_rate'],
                'notes' => $data['notes'],
            ]);

            if ($oldRoomId !== $newRoomId) {
                $this->releaseRoomIfIdle($oldRoomId, $staffId, $id);
            }
            $this->syncRoomStatusAfterBooking($newRoomId, $staffId);

            $pdo->commit();

            return ['ok' => true];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * @return array{ok: true}|array{ok: false, error: string}
     */
    public function cancel(int $id, ?string $reason, ?int $staffId): array
    {
        $existing = $this->reservations->findById($id);
        if ($existing === null) {
            return ['ok' => false, 'error' => 'Reservation not found.'];
        }

        if ((string) $existing['status'] !== 'booked') {
            return ['ok' => false, 'error' => 'Only booked reservations can be cancelled from this screen.'];
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $this->reservations->cancel($id, $reason !== null && trim($reason) !== '' ? trim($reason) : null);
            $this->releaseRoomIfIdle((int) $existing['room_id'], $staffId, $id);
            $pdo->commit();

            return ['ok' => true];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Build tape-chart rows: each room with bars positioned over date columns.
     *
     * @return array{dates: list<string>, rooms: list<array<string, mixed>>}
     */
    public function calendarPayload(string $from, int $days = 14): array
    {
        $days = max(7, min(28, $days));
        $start = new \DateTimeImmutable($from);
        $dates = [];
        for ($i = 0; $i < $days; $i++) {
            $dates[] = $start->modify("+{$i} days")->format('Y-m-d');
        }
        $to = $start->modify("+{$days} days")->format('Y-m-d');

        $rooms = $this->rooms->filtered([]);
        $reservations = $this->reservations->forCalendar($from, $to);

        $byRoom = [];
        foreach ($reservations as $res) {
            $byRoom[(int) $res['room_id']][] = $res;
        }

        $rows = [];
        foreach ($rooms as $room) {
            $roomId = (int) $room['id'];
            $bars = [];
            foreach ($byRoom[$roomId] ?? [] as $res) {
                $ci = max($res['check_in_date'], $from);
                $co = min($res['check_out_date'], $to);
                $startIdx = array_search($ci, $dates, true);
                if ($startIdx === false) {
                    continue;
                }
                $endExclusive = array_search($co, $dates, true);
                $span = $endExclusive === false
                    ? ($days - (int) $startIdx)
                    : ((int) $endExclusive - (int) $startIdx);
                if ($span < 1) {
                    continue;
                }
                $bars[] = [
                    'id' => (int) $res['id'],
                    'guest_name' => (string) $res['guest_name'],
                    'status' => (string) $res['status'],
                    'reference' => (string) $res['booking_reference'],
                    'col_start' => (int) $startIdx + 1, // 1-based after label col
                    'span' => $span,
                ];
            }
            $rows[] = [
                'id' => $roomId,
                'room_number' => (string) $room['room_number'],
                'room_type_name' => (string) $room['room_type_name'],
                'status' => (string) $room['status'],
                'bars' => $bars,
            ];
        }

        return ['dates' => $dates, 'rooms' => $rows];
    }

    public function defaultRateForRoom(int $roomId): ?string
    {
        $room = $this->rooms->findById($roomId);
        if ($room === null) {
            return null;
        }

        return (string) $room['base_rate'];
    }

    /**
     * @param array<string, mixed> $data
     * @return array{ok: false, error: string}|null
     */
    private function validateStay(array $data): ?array
    {
        if ($this->guests->findById((int) $data['guest_id']) === null) {
            return ['ok' => false, 'error' => 'Select a valid guest.'];
        }

        $room = $this->rooms->findById((int) $data['room_id']);
        if ($room === null) {
            return ['ok' => false, 'error' => 'Select a valid room.'];
        }

        if ((string) $room['status'] === 'maintenance') {
            return ['ok' => false, 'error' => 'That room is under maintenance and cannot be booked.'];
        }

        $checkIn = (string) $data['check_in_date'];
        $checkOut = (string) $data['check_out_date'];
        if ($checkOut <= $checkIn) {
            return ['ok' => false, 'error' => 'Check-out must be after check-in.'];
        }

        if (!in_array((string) $data['source'], Reservation::SOURCES, true)) {
            return ['ok' => false, 'error' => 'Select a valid booking source.'];
        }

        if ((int) $data['adults'] < 1) {
            return ['ok' => false, 'error' => 'At least one adult is required.'];
        }

        return null;
    }

    private function syncRoomStatusAfterBooking(int $roomId, ?int $staffId): void
    {
        $room = $this->rooms->findById($roomId);
        if ($room === null) {
            return;
        }

        // Mark available rooms as reserved when a booking holds them.
        if ((string) $room['status'] === 'available') {
            $this->rooms->update($roomId, [
                'room_type_id' => (int) $room['room_type_id'],
                'room_number' => (string) $room['room_number'],
                'floor' => $room['floor'],
                'status' => 'reserved',
                'notes' => $room['notes'],
                'changed_by' => $staffId,
                'status_reason' => 'Reservation created',
            ], (string) $room['status']);
        }
    }

    private function releaseRoomIfIdle(int $roomId, ?int $staffId, ?int $exceptReservationId = null): void
    {
        if ($this->reservations->countActiveForRoom($roomId, $exceptReservationId) > 0) {
            return;
        }

        $room = $this->rooms->findById($roomId);
        if ($room === null) {
            return;
        }

        if ((string) $room['status'] === 'reserved') {
            $this->rooms->update($roomId, [
                'room_type_id' => (int) $room['room_type_id'],
                'room_number' => (string) $room['room_number'],
                'floor' => $room['floor'],
                'status' => 'available',
                'notes' => $room['notes'],
                'changed_by' => $staffId,
                'status_reason' => 'Reservation cancelled or moved',
            ], (string) $room['status']);
        }
    }
}
