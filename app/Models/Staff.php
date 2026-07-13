<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Staff
{
    public const STATUSES = ['active', 'suspended'];

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
                    s.last_login_at, s.created_at, r.name AS role_name, r.description AS role_description
             FROM staff s
             INNER JOIN roles r ON r.id = s.role_id
             WHERE s.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * @param array{status?: string|null, role_id?: int|null, q?: string|null} $filters
     * @return list<array<string, mixed>>
     */
    public function filtered(array $filters = [], int $limit = 200): array
    {
        $sql = 'SELECT s.id, s.role_id, s.full_name, s.email, s.phone, s.status,
                       s.last_login_at, r.name AS role_name
                FROM staff s
                INNER JOIN roles r ON r.id = s.role_id
                WHERE 1=1';
        $params = [];

        if (!empty($filters['status']) && in_array($filters['status'], self::STATUSES, true)) {
            $sql .= ' AND s.status = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['role_id'])) {
            $sql .= ' AND s.role_id = :role_id';
            $params['role_id'] = (int) $filters['role_id'];
        }

        if (!empty($filters['q'])) {
            $sql .= ' AND (
                s.full_name LIKE :q
                OR s.email LIKE :q
                OR s.phone LIKE :q
            )';
            $params['q'] = '%' . $filters['q'] . '%';
        }

        $sql .= ' ORDER BY s.full_name ASC LIMIT ' . max(1, min(500, $limit));

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        /** @var list<array<string, mixed>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rows;
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

    /**
     * Active staff for assignment dropdowns.
     *
     * @return list<array<string, mixed>>
     */
    public function activeList(): array
    {
        $stmt = Database::connection()->query(
            'SELECT s.id, s.full_name, s.email, r.name AS role_name
             FROM staff s
             INNER JOIN roles r ON r.id = s.role_id
             WHERE s.status = \'active\'
             ORDER BY s.full_name ASC'
        );

        /** @var list<array<string, mixed>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rows;
    }

    public function emailExists(string $email, ?int $exceptId = null): bool
    {
        $sql = 'SELECT 1 FROM staff WHERE email = :email';
        $params = ['email' => $email];
        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $exceptId;
        }
        $sql .= ' LIMIT 1';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetchColumn();
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO staff (role_id, full_name, email, phone, password_hash, status)
             VALUES (:role_id, :full_name, :email, :phone, :password_hash, :status)'
        );
        $stmt->execute([
            'role_id' => $data['role_id'],
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password_hash' => $data['password_hash'],
            'status' => $data['status'],
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE staff
             SET role_id = :role_id,
                 full_name = :full_name,
                 email = :email,
                 phone = :phone,
                 status = :status
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'role_id' => $data['role_id'],
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'status' => $data['status'],
        ]);
    }

    public function updatePassword(int $id, string $passwordHash): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE staff SET password_hash = :password_hash WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'password_hash' => $passwordHash,
        ]);
    }
}
