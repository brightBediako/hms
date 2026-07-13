<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Staff
{
    /** @return array<string, mixed>|null */
    public function findByEmail(string $email): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT s.id, s.role_id, s.full_name, s.email, s.phone, s.password_hash, s.status,
                    s.last_login_at, r.name AS role_name
             FROM staff s
             INNER JOIN roles r ON r.id = s.role_id
             WHERE s.email = :email
             LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT s.id, s.role_id, s.full_name, s.email, s.phone, s.status,
                    s.last_login_at, r.name AS role_name
             FROM staff s
             INNER JOIN roles r ON r.id = s.role_id
             WHERE s.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /** @return list<string> */
    public function permissionKeysForRole(int $roleId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT p.`key`
             FROM role_permissions rp
             INNER JOIN permissions p ON p.id = rp.permission_id
             WHERE rp.role_id = :role_id
             ORDER BY p.`key`'
        );
        $stmt->execute(['role_id' => $roleId]);

        /** @var list<string> $keys */
        $keys = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return $keys;
    }

    public function touchLastLogin(int $staffId): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE staff SET last_login_at = NOW() WHERE id = :id'
        );
        $stmt->execute(['id' => $staffId]);
    }
}
