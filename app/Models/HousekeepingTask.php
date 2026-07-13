<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class HousekeepingTask
{
    public const TYPES = [
        'checkout_clean',
        'daily_clean',
        'deep_clean',
        'inspection',
    ];

    public const STATUSES = [
        'pending',
        'in_progress',
        'completed',
        'verified',
    ];

    public const OPEN_STATUSES = ['pending', 'in_progress'];

    public function createCheckoutClean(int $roomId, ?string $notes = null, ?string $scheduledFor = null): int
    {
        return $this->create([
            'room_id' => $roomId,
            'assigned_to' => null,
            'task_type' => 'checkout_clean',
            'status' => 'pending',
            'scheduled_for' => $scheduledFor ?? date('Y-m-d'),
            'notes' => $notes,
        ]);
    }

    /**
     * @param array{status?: string|null, task_type?: string|null, scheduled_for?: string|null, assigned_to?: int|null, q?: string|null} $filters
     * @return list<array<string, mixed>>
     */
    public function filtered(array $filters = [], int $limit = 200): array
    {
        $sql = 'SELECT t.*,
                       rm.room_number,
                       rm.floor,
                       rm.status AS room_status,
                       rt.name AS room_type_name,
                       s.full_name AS assigned_to_name
                FROM housekeeping_tasks t
                INNER JOIN rooms rm ON rm.id = t.room_id
                INNER JOIN room_types rt ON rt.id = rm.room_type_id
                LEFT JOIN staff s ON s.id = t.assigned_to
                WHERE 1=1';
        $params = [];

        if (!empty($filters['status']) && in_array($filters['status'], self::STATUSES, true)) {
            $sql .= ' AND t.status = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['task_type']) && in_array($filters['task_type'], self::TYPES, true)) {
            $sql .= ' AND t.task_type = :task_type';
            $params['task_type'] = $filters['task_type'];
        }

        if (!empty($filters['scheduled_for'])) {
            $sql .= ' AND t.scheduled_for = :scheduled_for';
            $params['scheduled_for'] = $filters['scheduled_for'];
        }

        if (isset($filters['assigned_to']) && $filters['assigned_to'] !== null && $filters['assigned_to'] !== '') {
            if ((int) $filters['assigned_to'] === 0) {
                $sql .= ' AND t.assigned_to IS NULL';
            } else {
                $sql .= ' AND t.assigned_to = :assigned_to';
                $params['assigned_to'] = (int) $filters['assigned_to'];
            }
        }

        if (!empty($filters['q'])) {
            $sql .= ' AND (
                rm.room_number LIKE :q
                OR rt.name LIKE :q
                OR t.notes LIKE :q
                OR s.full_name LIKE :q
            )';
            $params['q'] = '%' . $filters['q'] . '%';
        }

        $sql .= ' ORDER BY
                    FIELD(t.status, \'pending\', \'in_progress\', \'completed\', \'verified\'),
                    t.scheduled_for ASC,
                    rm.room_number ASC
                  LIMIT ' . max(1, min(500, $limit));

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
            'SELECT t.*,
                    rm.room_number,
                    rm.floor,
                    rm.status AS room_status,
                    rt.name AS room_type_name,
                    s.full_name AS assigned_to_name
             FROM housekeeping_tasks t
             INNER JOIN rooms rm ON rm.id = t.room_id
             INNER JOIN room_types rt ON rt.id = rm.room_type_id
             LEFT JOIN staff s ON s.id = t.assigned_to
             WHERE t.id = :id
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
            'INSERT INTO housekeeping_tasks
                (room_id, assigned_to, task_type, status, scheduled_for, notes)
             VALUES
                (:room_id, :assigned_to, :task_type, :status, :scheduled_for, :notes)'
        );
        $stmt->execute([
            'room_id' => $data['room_id'],
            'assigned_to' => $data['assigned_to'] ?? null,
            'task_type' => $data['task_type'],
            'status' => $data['status'] ?? 'pending',
            'scheduled_for' => $data['scheduled_for'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public function assign(int $id, ?int $staffId): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE housekeeping_tasks SET assigned_to = :assigned_to WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'assigned_to' => $staffId,
        ]);
    }

    public function markInProgress(int $id): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE housekeeping_tasks SET
                status = :status,
                started_at = COALESCE(started_at, CURRENT_TIMESTAMP)
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'status' => 'in_progress',
        ]);
    }

    public function markCompleted(int $id): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE housekeeping_tasks SET
                status = :status,
                completed_at = CURRENT_TIMESTAMP,
                started_at = COALESCE(started_at, CURRENT_TIMESTAMP)
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'status' => 'completed',
        ]);
    }

    public function markVerified(int $id): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE housekeeping_tasks SET status = :status WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'status' => 'verified',
        ]);
    }

    public function updateNotes(int $id, ?string $notes): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE housekeeping_tasks SET notes = :notes WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'notes' => $notes,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function pendingForRoom(int $roomId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM housekeeping_tasks
             WHERE room_id = :room_id
               AND status IN (\'pending\', \'in_progress\')
             ORDER BY created_at DESC'
        );
        $stmt->execute(['room_id' => $roomId]);

        /** @var list<array<string, mixed>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rows;
    }

    /** @return array<string, int> */
    public function statusCounts(?string $scheduledFor = null): array
    {
        if ($scheduledFor === null) {
            $stmt = Database::connection()->query(
                'SELECT status, COUNT(*) AS total FROM housekeeping_tasks GROUP BY status'
            );
        } else {
            $stmt = Database::connection()->prepare(
                'SELECT status, COUNT(*) AS total FROM housekeeping_tasks
                 WHERE scheduled_for = :d1
                    OR (scheduled_for IS NULL AND DATE(created_at) = :d2)
                 GROUP BY status'
            );
            $stmt->execute(['d1' => $scheduledFor, 'd2' => $scheduledFor]);
        }

        $counts = array_fill_keys(self::STATUSES, 0);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $counts[(string) $row['status']] = (int) $row['total'];
        }

        return $counts;
    }
}
