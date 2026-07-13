<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class AuditLog
{
    /**
     * @param array{
     *   action?: string|null,
     *   table_name?: string|null,
     *   staff_id?: int|null,
     *   date_from?: string|null,
     *   date_to?: string|null,
     *   q?: string|null
     * } $filters
     * @return list<array<string, mixed>>
     */
    public function filtered(array $filters = [], int $limit = 200): array
    {
        $sql = 'SELECT a.*,
                       s.full_name AS staff_name,
                       s.email AS staff_email
                FROM audit_logs a
                LEFT JOIN staff s ON s.id = a.staff_id
                WHERE 1=1';
        $params = [];

        if (!empty($filters['action'])) {
            $sql .= ' AND a.action = :action';
            $params['action'] = $filters['action'];
        }

        if (!empty($filters['table_name'])) {
            $sql .= ' AND a.table_name = :table_name';
            $params['table_name'] = $filters['table_name'];
        }

        if (!empty($filters['staff_id'])) {
            $sql .= ' AND a.staff_id = :staff_id';
            $params['staff_id'] = (int) $filters['staff_id'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= ' AND DATE(a.created_at) >= :date_from';
            $params['date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= ' AND DATE(a.created_at) <= :date_to';
            $params['date_to'] = $filters['date_to'];
        }

        if (!empty($filters['q'])) {
            $sql .= ' AND (
                a.action LIKE :q1
                OR a.table_name LIKE :q2
                OR CAST(a.record_id AS CHAR) LIKE :q3
                OR s.full_name LIKE :q4
                OR s.email LIKE :q5
            )';
            $like = '%' . $filters['q'] . '%';
            $params['q1'] = $like;
            $params['q2'] = $like;
            $params['q3'] = $like;
            $params['q4'] = $like;
            $params['q5'] = $like;
        }

        $sql .= ' ORDER BY a.created_at DESC, a.id DESC LIMIT ' . max(1, min(500, $limit));

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
            'SELECT a.*,
                    s.full_name AS staff_name,
                    s.email AS staff_email
             FROM audit_logs a
             LEFT JOIN staff s ON s.id = a.staff_id
             WHERE a.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * @param array{
     *   staff_id: ?int,
     *   action: string,
     *   table_name: ?string,
     *   record_id: ?int,
     *   old_values: ?string,
     *   new_values: ?string,
     *   ip_address: ?string
     * } $data
     */
    public function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO audit_logs (staff_id, action, table_name, record_id, old_values, new_values, ip_address)
             VALUES (:staff_id, :action, :table_name, :record_id, :old_values, :new_values, :ip_address)'
        );
        $stmt->execute([
            'staff_id' => $data['staff_id'],
            'action' => $data['action'],
            'table_name' => $data['table_name'],
            'record_id' => $data['record_id'],
            'old_values' => $data['old_values'],
            'new_values' => $data['new_values'],
            'ip_address' => $data['ip_address'],
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    /** @return list<string> */
    public function distinctActions(int $limit = 100): array
    {
        $stmt = Database::connection()->query(
            'SELECT DISTINCT action FROM audit_logs ORDER BY action ASC LIMIT ' . max(1, min(200, $limit))
        );

        /** @var list<string> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return $rows;
    }

    /** @return list<string> */
    public function distinctTables(int $limit = 50): array
    {
        $stmt = Database::connection()->query(
            'SELECT DISTINCT table_name FROM audit_logs
             WHERE table_name IS NOT NULL
             ORDER BY table_name ASC LIMIT ' . max(1, min(100, $limit))
        );

        /** @var list<string> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return $rows;
    }
}
