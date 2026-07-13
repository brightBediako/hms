<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Reservation
{
    public const STATUSES = [
        'booked',
        'checked_in',
        'checked_out',
        'cancelled',
        'no_show',
    ];

    public const ACTIVE_STATUSES = ['booked', 'checked_in'];

    public const SOURCES = [
        'walk_in',
        'phone',
        'advance',
        'other',
    ];

    /**
     * @param array{status?: string|null, q?: string|null, from?: string|null, to?: string|null} $filters
     * @return list<array<string, mixed>>
     */
    public function filtered(array $filters = [], int $limit = 200): array
    {
        $sql = 'SELECT r.*,
                       g.full_name AS guest_name,
                       g.phone AS guest_phone,
                       rm.room_number,
                       rt.name AS room_type_name
                FROM reservations r
                INNER JOIN guests g ON g.id = r.guest_id
                INNER JOIN rooms rm ON rm.id = r.room_id
                INNER JOIN room_types rt ON rt.id = rm.room_type_id
                WHERE 1=1';
        $params = [];

        if (!empty($filters['status']) && in_array($filters['status'], self::STATUSES, true)) {
            $sql .= ' AND r.status = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['from'])) {
            $sql .= ' AND r.check_out_date > :from_date';
            $params['from_date'] = $filters['from'];
        }

        if (!empty($filters['to'])) {
            $sql .= ' AND r.check_in_date < :to_date';
            $params['to_date'] = $filters['to'];
        }

        if (!empty($filters['q'])) {
            $sql .= ' AND (
                r.booking_reference LIKE :q
                OR g.full_name LIKE :q
                OR g.phone LIKE :q
                OR rm.room_number LIKE :q
            )';
            $params['q'] = '%' . $filters['q'] . '%';
        }

        $sql .= ' ORDER BY r.check_in_date DESC, r.id DESC LIMIT ' . max(1, min(500, $limit));

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        /** @var list<array<string, mixed>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rows;
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT r.*,
                    g.full_name AS guest_name,
                    g.email AS guest_email,
                    g.phone AS guest_phone,
                    rm.room_number,
                    rm.status AS room_status,
                    rt.name AS room_type_name,
                    rt.base_rate AS room_type_base_rate,
                    s.full_name AS booked_by_name
             FROM reservations r
             INNER JOIN guests g ON g.id = r.guest_id
             INNER JOIN rooms rm ON rm.id = r.room_id
             INNER JOIN room_types rt ON rt.id = rm.room_type_id
             LEFT JOIN staff s ON s.id = r.booked_by
             WHERE r.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO reservations (
                booking_reference, guest_id, room_id, booked_by, source,
                check_in_date, check_out_date, check_in_time, check_out_time,
                adults, children, agreed_rate, status, notes
             ) VALUES (
                :booking_reference, :guest_id, :room_id, :booked_by, :source,
                :check_in_date, :check_out_date, :check_in_time, :check_out_time,
                :adults, :children, :agreed_rate, :status, :notes
             )'
        );
        $stmt->execute([
            'booking_reference' => $data['booking_reference'],
            'guest_id' => $data['guest_id'],
            'room_id' => $data['room_id'],
            'booked_by' => $data['booked_by'],
            'source' => $data['source'],
            'check_in_date' => $data['check_in_date'],
            'check_out_date' => $data['check_out_date'],
            'check_in_time' => $data['check_in_time'],
            'check_out_time' => $data['check_out_time'],
            'adults' => $data['adults'],
            'children' => $data['children'],
            'agreed_rate' => $data['agreed_rate'],
            'status' => $data['status'],
            'notes' => $data['notes'],
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE reservations SET
                guest_id = :guest_id,
                room_id = :room_id,
                source = :source,
                check_in_date = :check_in_date,
                check_out_date = :check_out_date,
                check_in_time = :check_in_time,
                check_out_time = :check_out_time,
                adults = :adults,
                children = :children,
                agreed_rate = :agreed_rate,
                notes = :notes
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'guest_id' => $data['guest_id'],
            'room_id' => $data['room_id'],
            'source' => $data['source'],
            'check_in_date' => $data['check_in_date'],
            'check_out_date' => $data['check_out_date'],
            'check_in_time' => $data['check_in_time'],
            'check_out_time' => $data['check_out_time'],
            'adults' => $data['adults'],
            'children' => $data['children'],
            'agreed_rate' => $data['agreed_rate'],
            'notes' => $data['notes'],
        ]);
    }

    public function cancel(int $id, ?string $reason): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE reservations SET
                status = :status,
                cancellation_reason = :cancellation_reason
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'status' => 'cancelled',
            'cancellation_reason' => $reason,
        ]);
    }

    public function markCheckedIn(int $id, int $roomId): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE reservations SET
                status = :status,
                room_id = :room_id,
                actual_check_in = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'status' => 'checked_in',
            'room_id' => $roomId,
        ]);
    }

    public function markCheckedOut(int $id): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE reservations SET
                status = :status,
                actual_check_out = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'status' => 'checked_out',
        ]);
    }

    public function assignRoom(int $id, int $roomId): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE reservations SET room_id = :room_id WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'room_id' => $roomId,
        ]);
    }

    public function extendStay(int $id, string $newCheckOut): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE reservations SET check_out_date = :check_out_date WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'check_out_date' => $newCheckOut,
        ]);
    }

    /**
     * Booked arrivals for a date (includes overdue arrivals still booked).
     *
     * @return list<array<string, mixed>>
     */
    public function arrivalsForDate(string $date): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT r.*,
                    g.full_name AS guest_name,
                    g.phone AS guest_phone,
                    g.id_type AS guest_id_type,
                    g.id_number AS guest_id_number,
                    rm.room_number,
                    rm.status AS room_status,
                    rt.name AS room_type_name
             FROM reservations r
             INNER JOIN guests g ON g.id = r.guest_id
             INNER JOIN rooms rm ON rm.id = r.room_id
             INNER JOIN room_types rt ON rt.id = rm.room_type_id
             WHERE r.status = \'booked\'
               AND r.check_in_date <= :date
             ORDER BY r.check_in_date ASC, g.full_name ASC'
        );
        $stmt->execute(['date' => $date]);

        /** @var list<array<string, mixed>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rows;
    }

    /**
     * In-house departures due on or before date.
     *
     * @return list<array<string, mixed>>
     */
    public function departuresForDate(string $date): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT r.*,
                    g.full_name AS guest_name,
                    g.phone AS guest_phone,
                    rm.room_number,
                    rm.status AS room_status,
                    rt.name AS room_type_name
             FROM reservations r
             INNER JOIN guests g ON g.id = r.guest_id
             INNER JOIN rooms rm ON rm.id = r.room_id
             INNER JOIN room_types rt ON rt.id = rm.room_type_id
             WHERE r.status = \'checked_in\'
               AND r.check_out_date <= :date
             ORDER BY r.check_out_date ASC, g.full_name ASC'
        );
        $stmt->execute(['date' => $date]);

        /** @var list<array<string, mixed>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function inHouse(): array
    {
        $stmt = Database::connection()->query(
            'SELECT r.*,
                    g.full_name AS guest_name,
                    g.phone AS guest_phone,
                    rm.room_number,
                    rm.status AS room_status,
                    rt.name AS room_type_name
             FROM reservations r
             INNER JOIN guests g ON g.id = r.guest_id
             INNER JOIN rooms rm ON rm.id = r.room_id
             INNER JOIN room_types rt ON rt.id = rm.room_type_id
             WHERE r.status = \'checked_in\'
             ORDER BY rm.room_number ASC'
        );

        /** @var list<array<string, mixed>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rows;
    }

    public function nextReferenceNumber(int $year): int
    {
        $prefix = 'HMS-' . $year . '-';
        $stmt = Database::connection()->prepare(
            'SELECT booking_reference FROM reservations
             WHERE booking_reference LIKE :prefix
             ORDER BY booking_reference DESC
             LIMIT 1'
        );
        $stmt->execute(['prefix' => $prefix . '%']);
        $last = $stmt->fetchColumn();
        if (!is_string($last) || $last === '') {
            return 1;
        }

        $parts = explode('-', $last);
        $seq = (int) end($parts);

        return $seq + 1;
    }

    /**
     * Overlapping active stays for a room (half-open nights: check_out exclusive).
     *
     * @return list<array<string, mixed>>
     */
    public function overlapping(int $roomId, string $checkIn, string $checkOut, ?int $exceptId = null): array
    {
        $sql = 'SELECT id, booking_reference, check_in_date, check_out_date, status, guest_id
                FROM reservations
                WHERE room_id = :room_id
                  AND status IN (\'booked\', \'checked_in\')
                  AND check_in_date < :check_out
                  AND check_out_date > :check_in';
        $params = [
            'room_id' => $roomId,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
        ];

        if ($exceptId !== null) {
            $sql .= ' AND id != :except_id';
            $params['except_id'] = $exceptId;
        }

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        /** @var list<array<string, mixed>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rows;
    }

    /**
     * Active reservations that intersect [from, to) for calendar.
     *
     * @return list<array<string, mixed>>
     */
    public function forCalendar(string $from, string $to): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT r.id, r.room_id, r.guest_id, r.check_in_date, r.check_out_date, r.status,
                    r.booking_reference, g.full_name AS guest_name, rm.room_number
             FROM reservations r
             INNER JOIN guests g ON g.id = r.guest_id
             INNER JOIN rooms rm ON rm.id = r.room_id
             WHERE r.status IN (\'booked\', \'checked_in\', \'checked_out\')
               AND r.check_in_date < :to_date
               AND r.check_out_date > :from_date
             ORDER BY rm.room_number ASC, r.check_in_date ASC'
        );
        $stmt->execute([
            'from_date' => $from,
            'to_date' => $to,
        ]);

        /** @var list<array<string, mixed>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rows;
    }

    public function countActiveForRoom(int $roomId, ?int $exceptId = null): int
    {
        $sql = 'SELECT COUNT(*) FROM reservations
                WHERE room_id = :room_id
                  AND status IN (\'booked\', \'checked_in\')';
        $params = ['room_id' => $roomId];
        if ($exceptId !== null) {
            $sql .= ' AND id != :except_id';
            $params['except_id'] = $exceptId;
        }
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }
}
