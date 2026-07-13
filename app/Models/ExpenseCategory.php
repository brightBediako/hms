<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class ExpenseCategory
{
    /**
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        $stmt = Database::connection()->query(
            'SELECT id, name FROM expense_categories ORDER BY name ASC'
        );

        /** @var list<array<string, mixed>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rows;
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, name FROM expense_categories WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function create(string $name): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO expense_categories (name) VALUES (:name)'
        );
        $stmt->execute(['name' => $name]);

        return (int) Database::connection()->lastInsertId();
    }
}
