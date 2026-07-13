<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Room
{
    public const STATUSES = [
        'available',
        'occupied',
        'reserved',
        'cleaning',
        'maintenance',
    ];

    /**
     * @param array{floor?: string|null, type_ids?: list<int>, statuses?: list<string>, q?: string|null} $filters
     * @return list<array<string, mixed>>
     */
    public function filtered(array $filters = []): array
    {
        $sql = 'SELECT r.*,
                       rt.name AS room_type_name,
                       rt.amenities AS room_type_amenities,
                       rt.base_rate
                FROM rooms r
                INNER JOIN room_types rt ON rt.id = r.room_type_id
                WHERE 1=1';
        $params = [];

        if (!empty($filters['floor'])) {
            $sql .= ' AND r.floor = :floor';
            $params['floor'] = $filters['floor'];
        }

        if (!empty($filters['type_ids']) && is_array($filters['type_ids'])) {
            $ids = array_values(array_filter(array_map('intval', $filters['type_ids'])));
            if ($ids !== []) {
                $placeholders = [];
                foreach ($ids as $i => $id) {
                    $key = 'type_' . $i;
                    $placeholders[] = ':' . $key;
                    $params[$key] = $id;
                }
                $sql .= ' AND r.room_type_id IN (' . implode(',', $placeholders) . ')';
            }
        }

        if (!empty($filters['statuses']) && is_array($filters['statuses'])) {
            $statuses = array_values(array_intersect(self::STATUSES, $filters['statuses']));
            if ($statuses !== []) {
                $placeholders = [];
                foreach ($statuses as $i => $status) {
                    $key = 'status_' . $i;
                    $placeholders[] = ':' . $key;
                    $params[$key] = $status;
                }
                $sql .= ' AND r.status IN (' . implode(',', $placeholders) . ')';
            }
        }

        if (!empty($filters['q'])) {
            $sql .= ' AND (r.room_number LIKE :q OR r.notes LIKE :q OR rt.name LIKE :q)';
            $params['q'] = '%' . $filters['q'] . '%';
        }

        $sql .= ' ORDER BY r.floor ASC, r.room_number ASC';

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
                    rt.name AS room_type_name,
                    rt.amenities AS room_type_amenities,
                    rt.base_capacity_adults,
                    rt.base_capacity_children,
                    rt.base_rate,
                    rt.extra_bed_rate
             FROM rooms r
             INNER JOIN room_types rt ON rt.id = r.room_type_id
             WHERE r.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /** @return list<string> */
    public function distinctFloors(): array
    {
        $stmt = Database::connection()->query(
            "SELECT DISTINCT floor FROM rooms WHERE floor IS NOT NULL AND floor != '' ORDER BY floor ASC"
        );

        /** @var list<string> $floors */
        $floors = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return $floors;
    }

    /** @return array<string, int> */
    public function statusCounts(): array
    {
        $stmt = Database::connection()->query(
            'SELECT status, COUNT(*) AS total FROM rooms GROUP BY status'
        );
        $counts = array_fill_keys(self::STATUSES, 0);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $counts[(string) $row['status']] = (int) $row['total'];
        }

        return $counts;
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO rooms (room_type_id, room_number, floor, status, notes)
             VALUES (:room_type_id, :room_number, :floor, :status, :notes)'
        );
        $stmt->execute([
            'room_type_id' => $data['room_type_id'],
            'room_number' => $data['room_number'],
            'floor' => $data['floor'],
            'status' => $data['status'],
            'notes' => $data['notes'],
        ]);

        $id = (int) Database::connection()->lastInsertId();
        $this->logStatusChange($id, null, (string) $data['status'], $data['changed_by'] ?? null, 'Room created');

        return $id;
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data, ?string $previousStatus = null): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE rooms SET
                room_type_id = :room_type_id,
                room_number = :room_number,
                floor = :floor,
                status = :status,
                notes = :notes
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'room_type_id' => $data['room_type_id'],
            'room_number' => $data['room_number'],
            'floor' => $data['floor'],
            'status' => $data['status'],
            'notes' => $data['notes'],
        ]);

        if ($previousStatus !== null && $previousStatus !== $data['status']) {
            $this->logStatusChange(
                $id,
                $previousStatus,
                (string) $data['status'],
                $data['changed_by'] ?? null,
                $data['status_reason'] ?? 'Manual status update'
            );
        }
    }

    public function roomNumberExists(string $roomNumber, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM rooms WHERE room_number = :room_number';
        $params = ['room_number' => $roomNumber];
        if ($exceptId !== null) {
            $sql .= ' AND id != :id';
            $params['id'] = $exceptId;
        }
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }

    /** @return list<array<string, mixed>> */
    public function statusHistory(int $roomId, int $limit = 10): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT l.*, s.full_name AS changed_by_name
             FROM room_status_log l
             LEFT JOIN staff s ON s.id = l.changed_by
             WHERE l.room_id = :room_id
             ORDER BY l.changed_at DESC
             LIMIT ' . max(1, min(50, $limit))
        );
        $stmt->execute(['room_id' => $roomId]);

        /** @var list<array<string, mixed>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rows;
    }

    public function logStatusChange(
        int $roomId,
        ?string $oldStatus,
        string $newStatus,
        ?int $changedBy,
        ?string $reason = null
    ): void {
        $stmt = Database::connection()->prepare(
            'INSERT INTO room_status_log (room_id, old_status, new_status, changed_by, reason)
             VALUES (:room_id, :old_status, :new_status, :changed_by, :reason)'
        );
        $stmt->execute([
            'room_id' => $roomId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => $changedBy,
            'reason' => $reason,
        ]);
    }
}
