<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class InvoiceItem
{
    public const TYPES = [
        'room_charge',
        'service',
        'discount',
        'tax',
        'other',
    ];

    /**
     * @return list<array<string, mixed>>
     */
    public function forInvoice(int $invoiceId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM invoice_items
             WHERE invoice_id = :invoice_id
             ORDER BY id ASC'
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
            'SELECT * FROM invoice_items WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO invoice_items
                (invoice_id, item_type, description, quantity, unit_price, line_total, source_module)
             VALUES
                (:invoice_id, :item_type, :description, :quantity, :unit_price, :line_total, :source_module)'
        );
        $stmt->execute([
            'invoice_id' => $data['invoice_id'],
            'item_type' => $data['item_type'],
            'description' => $data['description'],
            'quantity' => $data['quantity'],
            'unit_price' => $data['unit_price'],
            'line_total' => $data['line_total'],
            'source_module' => $data['source_module'] ?? null,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public function delete(int $id): void
    {
        $stmt = Database::connection()->prepare(
            'DELETE FROM invoice_items WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
    }
}
