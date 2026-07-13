<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Expense
{
    /**
     * @param array{
     *   category_id?: int|null,
     *   date_from?: string|null,
     *   date_to?: string|null,
     *   q?: string|null
     * } $filters
     * @return list<array<string, mixed>>
     */
    public function filtered(array $filters = [], int $limit = 200): array
    {
        $sql = 'SELECT e.*,
                       c.name AS category_name,
                       s.full_name AS recorded_by_name
                FROM expenses e
                INNER JOIN expense_categories c ON c.id = e.category_id
                LEFT JOIN staff s ON s.id = e.recorded_by
                WHERE 1=1';
        $params = [];

        if (!empty($filters['category_id'])) {
            $sql .= ' AND e.category_id = :category_id';
            $params['category_id'] = (int) $filters['category_id'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= ' AND e.expense_date >= :date_from';
            $params['date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= ' AND e.expense_date <= :date_to';
            $params['date_to'] = $filters['date_to'];
        }

        if (!empty($filters['q'])) {
            $sql .= ' AND e.description LIKE :q';
            $params['q'] = '%' . $filters['q'] . '%';
        }

        $sql .= ' ORDER BY e.expense_date DESC, e.id DESC LIMIT ' . max(1, min(500, $limit));

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        /** @var list<array<string, mixed>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rows;
    }

    /**
     * @param array{
     *   category_id?: int|null,
     *   date_from?: string|null,
     *   date_to?: string|null,
     *   q?: string|null
     * } $filters
     */
    public function sumFiltered(array $filters = []): float
    {
        $sql = 'SELECT COALESCE(SUM(e.amount), 0)
                FROM expenses e
                WHERE 1=1';
        $params = [];

        if (!empty($filters['category_id'])) {
            $sql .= ' AND e.category_id = :category_id';
            $params['category_id'] = (int) $filters['category_id'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= ' AND e.expense_date >= :date_from';
            $params['date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= ' AND e.expense_date <= :date_to';
            $params['date_to'] = $filters['date_to'];
        }

        if (!empty($filters['q'])) {
            $sql .= ' AND e.description LIKE :q';
            $params['q'] = '%' . $filters['q'] . '%';
        }

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        return round((float) $stmt->fetchColumn(), 2);
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT e.*,
                    c.name AS category_name,
                    s.full_name AS recorded_by_name
             FROM expenses e
             INNER JOIN expense_categories c ON c.id = e.category_id
             LEFT JOIN staff s ON s.id = e.recorded_by
             WHERE e.id = :id
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
            'INSERT INTO expenses (category_id, description, amount, expense_date, recorded_by, receipt_path)
             VALUES (:category_id, :description, :amount, :expense_date, :recorded_by, :receipt_path)'
        );
        $stmt->execute([
            'category_id' => $data['category_id'],
            'description' => $data['description'],
            'amount' => $data['amount'],
            'expense_date' => $data['expense_date'],
            'recorded_by' => $data['recorded_by'],
            'receipt_path' => $data['receipt_path'],
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public function delete(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM expenses WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
