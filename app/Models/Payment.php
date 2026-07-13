<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Payment
{
    public const METHODS = [
        'cash',
        'mobile_money',
        'card',
        'bank_transfer',
        'other',
    ];

    /**
     * @param array{method?: string|null, q?: string|null, invoice_id?: int|null} $filters
     * @return list<array<string, mixed>>
     */
    public function filtered(array $filters = [], int $limit = 200): array
    {
        $sql = 'SELECT p.*,
                       i.invoice_number,
                       i.balance_due AS invoice_balance_due,
                       g.full_name AS guest_name,
                       s.full_name AS received_by_name
                FROM payments p
                INNER JOIN invoices i ON i.id = p.invoice_id
                INNER JOIN guests g ON g.id = i.guest_id
                LEFT JOIN staff s ON s.id = p.received_by
                WHERE 1=1';
        $params = [];

        if (!empty($filters['invoice_id'])) {
            $sql .= ' AND p.invoice_id = :invoice_id';
            $params['invoice_id'] = (int) $filters['invoice_id'];
        }

        if (!empty($filters['method']) && in_array($filters['method'], self::METHODS, true)) {
            $sql .= ' AND p.method = :method';
            $params['method'] = $filters['method'];
        }

        if (!empty($filters['q'])) {
            $sql .= ' AND (
                i.invoice_number LIKE :q
                OR g.full_name LIKE :q
                OR p.reference_number LIKE :q
            )';
            $params['q'] = '%' . $filters['q'] . '%';
        }

        $sql .= ' ORDER BY p.paid_at DESC, p.id DESC LIMIT ' . max(1, min(500, $limit));

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        /** @var list<array<string, mixed>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function forInvoice(int $invoiceId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT p.*, s.full_name AS received_by_name
             FROM payments p
             LEFT JOIN staff s ON s.id = p.received_by
             WHERE p.invoice_id = :invoice_id
             ORDER BY p.paid_at ASC, p.id ASC'
        );
        $stmt->execute(['invoice_id' => $invoiceId]);

        /** @var list<array<string, mixed>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rows;
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT p.*, i.invoice_number, g.full_name AS guest_name
             FROM payments p
             INNER JOIN invoices i ON i.id = p.invoice_id
             INNER JOIN guests g ON g.id = i.guest_id
             WHERE p.id = :id
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
            'INSERT INTO payments (invoice_id, method, amount, reference_number, received_by, notes)
             VALUES (:invoice_id, :method, :amount, :reference_number, :received_by, :notes)'
        );
        $stmt->execute([
            'invoice_id' => $data['invoice_id'],
            'method' => $data['method'],
            'amount' => $data['amount'],
            'reference_number' => $data['reference_number'],
            'received_by' => $data['received_by'],
            'notes' => $data['notes'],
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public function sumForInvoice(int $invoiceId): float
    {
        $stmt = Database::connection()->prepare(
            'SELECT COALESCE(SUM(amount), 0) FROM payments WHERE invoice_id = :invoice_id'
        );
        $stmt->execute(['invoice_id' => $invoiceId]);

        return round((float) $stmt->fetchColumn(), 2);
    }
}
