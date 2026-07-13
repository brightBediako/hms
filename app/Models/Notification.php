<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Notification
{
    /**
     * @return list<array<string, mixed>>
     */
    public function forStaff(int $staffId, ?bool $unreadOnly = null, int $limit = 100): array
    {
        $sql = 'SELECT * FROM notifications WHERE staff_id = :staff_id';
        $params = ['staff_id' => $staffId];

        if ($unreadOnly === true) {
            $sql .= ' AND is_read = 0';
        } elseif ($unreadOnly === false) {
            $sql .= ' AND is_read = 1';
        }

        $sql .= ' ORDER BY created_at DESC, id DESC LIMIT ' . max(1, min(300, $limit));

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
            'SELECT * FROM notifications WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function unreadCount(int $staffId): int
    {
        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*) FROM notifications WHERE staff_id = :staff_id AND is_read = 0'
        );
        $stmt->execute(['staff_id' => $staffId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * @param array{
     *   staff_id: int,
     *   title: string,
     *   message: string,
     *   type?: ?string,
     *   related_table?: ?string,
     *   related_id?: ?int
     * } $data
     */
    public function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO notifications (staff_id, title, message, type, related_table, related_id, is_read)
             VALUES (:staff_id, :title, :message, :type, :related_table, :related_id, 0)'
        );
        $stmt->execute([
            'staff_id' => $data['staff_id'],
            'title' => $data['title'],
            'message' => $data['message'],
            'type' => $data['type'] ?? null,
            'related_table' => $data['related_table'] ?? null,
            'related_id' => $data['related_id'] ?? null,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public function markRead(int $id, int $staffId): bool
    {
        $stmt = Database::connection()->prepare(
            'UPDATE notifications SET is_read = 1
             WHERE id = :id AND staff_id = :staff_id AND is_read = 0'
        );
        $stmt->execute(['id' => $id, 'staff_id' => $staffId]);

        return $stmt->rowCount() > 0;
    }

    public function markAllRead(int $staffId): int
    {
        $stmt = Database::connection()->prepare(
            'UPDATE notifications SET is_read = 1
             WHERE staff_id = :staff_id AND is_read = 0'
        );
        $stmt->execute(['staff_id' => $staffId]);

        return $stmt->rowCount();
    }

    /**
     * Active staff IDs that hold a permission via their role.
     *
     * @return list<int>
     */
    public function activeStaffIdsWithPermission(string $permissionKey): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT DISTINCT s.id
             FROM staff s
             INNER JOIN role_permissions rp ON rp.role_id = s.role_id
             INNER JOIN permissions p ON p.id = rp.permission_id
             WHERE s.status = \'active\'
               AND p.`key` = :permission_key
             ORDER BY s.id'
        );
        $stmt->execute(['permission_key' => $permissionKey]);

        /** @var list<int|string> $ids */
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return array_map('intval', $ids);
    }
}
