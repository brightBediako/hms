<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class MaintenanceRequest
{
    public const PRIORITIES = [
        'low',
        'medium',
        'high',
        'urgent',
    ];

    public const STATUSES = [
        'open',
        'in_progress',
        'resolved',
        'cancelled',
    ];

    public const OPEN_STATUSES = ['open', 'in_progress'];

    /**
     * @param array{status?: string|null, priority?: string|null, q?: string|null, room_id?: int|null} $filters
     * @return list<array<string, mixed>>
     */
    public function filtered(array $filters = [], int $limit = 200): array
    {
        $sql = 'SELECT m.*,
                       rm.room_number,
                       rm.floor,
                       rm.status AS room_status,
                       rt.name AS room_type_name,
                       reporter.full_name AS reported_by_name,
                       assignee.full_name AS assigned_to_name
                FROM maintenance_requests m
                LEFT JOIN rooms rm ON rm.id = m.room_id
                LEFT JOIN room_types rt ON rt.id = rm.room_type_id
                LEFT JOIN staff reporter ON reporter.id = m.reported_by
                LEFT JOIN staff assignee ON assignee.id = m.assigned_to
                WHERE 1=1';
        $params = [];

        if (!empty($filters['status']) && in_array($filters['status'], self::STATUSES, true)) {
            $sql .= ' AND m.status = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['priority']) && in_array($filters['priority'], self::PRIORITIES, true)) {
            $sql .= ' AND m.priority = :priority';
            $params['priority'] = $filters['priority'];
        }

        if (!empty($filters['room_id'])) {
            $sql .= ' AND m.room_id = :room_id';
            $params['room_id'] = (int) $filters['room_id'];
        }

        if (!empty($filters['q'])) {
            $sql .= ' AND (
                m.issue_title LIKE :q
                OR m.description LIKE :q
                OR rm.room_number LIKE :q
                OR assignee.full_name LIKE :q
            )';
            $params['q'] = '%' . $filters['q'] . '%';
        }

        $sql .= ' ORDER BY
                    FIELD(m.status, \'open\', \'in_progress\', \'resolved\', \'cancelled\'),
                    FIELD(m.priority, \'urgent\', \'high\', \'medium\', \'low\'),
                    m.reported_at DESC
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
            'SELECT m.*,
                    rm.room_number,
                    rm.floor,
                    rm.status AS room_status,
                    rt.name AS room_type_name,
                    reporter.full_name AS reported_by_name,
                    assignee.full_name AS assigned_to_name
             FROM maintenance_requests m
             LEFT JOIN rooms rm ON rm.id = m.room_id
             LEFT JOIN room_types rt ON rt.id = rm.room_type_id
             LEFT JOIN staff reporter ON reporter.id = m.reported_by
             LEFT JOIN staff assignee ON assignee.id = m.assigned_to
             WHERE m.id = :id
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
            'INSERT INTO maintenance_requests
                (room_id, reported_by, assigned_to, issue_title, description, priority, status)
             VALUES
                (:room_id, :reported_by, :assigned_to, :issue_title, :description, :priority, :status)'
        );
        $stmt->execute([
            'room_id' => $data['room_id'],
            'reported_by' => $data['reported_by'],
            'assigned_to' => $data['assigned_to'],
            'issue_title' => $data['issue_title'],
            'description' => $data['description'],
            'priority' => $data['priority'],
            'status' => $data['status'] ?? 'open',
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public function assign(int $id, ?int $staffId): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE maintenance_requests SET assigned_to = :assigned_to WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'assigned_to' => $staffId,
        ]);
    }

    public function markInProgress(int $id): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE maintenance_requests SET status = :status WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'status' => 'in_progress',
        ]);
    }

    public function markResolved(int $id): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE maintenance_requests SET
                status = :status,
                resolved_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'status' => 'resolved',
        ]);
    }

    public function markCancelled(int $id): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE maintenance_requests SET
                status = :status,
                resolved_at = COALESCE(resolved_at, CURRENT_TIMESTAMP)
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'status' => 'cancelled',
        ]);
    }

    public function countOpenForRoom(int $roomId, ?int $exceptId = null): int
    {
        $sql = 'SELECT COUNT(*) FROM maintenance_requests
                WHERE room_id = :room_id
                  AND status IN (\'open\', \'in_progress\')';
        $params = ['room_id' => $roomId];
        if ($exceptId !== null) {
            $sql .= ' AND id != :except_id';
            $params['except_id'] = $exceptId;
        }
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /** @return array<string, int> */
    public function statusCounts(): array
    {
        $stmt = Database::connection()->query(
            'SELECT status, COUNT(*) AS total FROM maintenance_requests GROUP BY status'
        );
        $counts = array_fill_keys(self::STATUSES, 0);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $counts[(string) $row['status']] = (int) $row['total'];
        }

        return $counts;
    }
}
