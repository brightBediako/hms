<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Role
{
    /**
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        $stmt = Database::connection()->query(
            'SELECT id, name, description FROM roles ORDER BY name ASC'
        );

        /** @var list<array<string, mixed>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rows;
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, name, description FROM roles WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * @return list<array{key: string, description: ?string}>
     */
    public function permissions(int $roleId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT p.`key`, p.description
             FROM role_permissions rp
             INNER JOIN permissions p ON p.id = rp.permission_id
             WHERE rp.role_id = :role_id
             ORDER BY p.`key`'
        );
        $stmt->execute(['role_id' => $roleId]);

        /** @var list<array{key: string, description: ?string}> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rows;
    }
}
