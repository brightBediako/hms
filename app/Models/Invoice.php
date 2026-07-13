<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Invoice
{
    public const STATUSES = [
        'draft',
        'issued',
        'partially_paid',
        'paid',
        'void',
    ];

    /**
     * @param array{status?: string|null, q?: string|null} $filters
     * @return list<array<string, mixed>>
     */
    public function filtered(array $filters = [], int $limit = 200): array
    {
        $sql = 'SELECT i.*,
                       g.full_name AS guest_name,
                       g.phone AS guest_phone,
                       r.booking_reference,
                       rm.room_number
                FROM invoices i
                INNER JOIN guests g ON g.id = i.guest_id
                INNER JOIN reservations r ON r.id = i.reservation_id
                INNER JOIN rooms rm ON rm.id = r.room_id
                WHERE 1=1';
        $params = [];

        if (!empty($filters['status']) && in_array($filters['status'], self::STATUSES, true)) {
            $sql .= ' AND i.status = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['q'])) {
            $sql .= ' AND (
                i.invoice_number LIKE :q
                OR g.full_name LIKE :q
                OR r.booking_reference LIKE :q
                OR rm.room_number LIKE :q
            )';
            $params['q'] = '%' . $filters['q'] . '%';
        }

        $sql .= ' ORDER BY i.created_at DESC, i.id DESC LIMIT ' . max(1, min(500, $limit));

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
            'SELECT i.*,
                    g.full_name AS guest_name,
                    g.email AS guest_email,
                    g.phone AS guest_phone,
                    g.address AS guest_address,
                    r.booking_reference,
                    r.check_in_date,
                    r.check_out_date,
                    r.agreed_rate,
                    r.status AS reservation_status,
                    rm.room_number,
                    rt.name AS room_type_name,
                    s.full_name AS issued_by_name
             FROM invoices i
             INNER JOIN guests g ON g.id = i.guest_id
             INNER JOIN reservations r ON r.id = i.reservation_id
             INNER JOIN rooms rm ON rm.id = r.room_id
             INNER JOIN room_types rt ON rt.id = rm.room_type_id
             LEFT JOIN staff s ON s.id = i.issued_by
             WHERE i.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /** @return array<string, mixed>|null */
    public function findActiveForReservation(int $reservationId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM invoices
             WHERE reservation_id = :reservation_id
               AND status != \'void\'
             ORDER BY id DESC
             LIMIT 1'
        );
        $stmt->execute(['reservation_id' => $reservationId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO invoices (
                invoice_number, reservation_id, guest_id,
                subtotal, discount_amount, tax_amount, total_amount,
                amount_paid, balance_due, status
             ) VALUES (
                :invoice_number, :reservation_id, :guest_id,
                :subtotal, :discount_amount, :tax_amount, :total_amount,
                :amount_paid, :balance_due, :status
             )'
        );
        $stmt->execute([
            'invoice_number' => $data['invoice_number'],
            'reservation_id' => $data['reservation_id'],
            'guest_id' => $data['guest_id'],
            'subtotal' => $data['subtotal'],
            'discount_amount' => $data['discount_amount'],
            'tax_amount' => $data['tax_amount'],
            'total_amount' => $data['total_amount'],
            'amount_paid' => $data['amount_paid'],
            'balance_due' => $data['balance_due'],
            'status' => $data['status'],
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    /** @param array<string, mixed> $totals */
    public function updateTotals(int $id, array $totals): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE invoices SET
                subtotal = :subtotal,
                discount_amount = :discount_amount,
                tax_amount = :tax_amount,
                total_amount = :total_amount,
                balance_due = :balance_due,
                status = :status
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'subtotal' => $totals['subtotal'],
            'discount_amount' => $totals['discount_amount'],
            'tax_amount' => $totals['tax_amount'],
            'total_amount' => $totals['total_amount'],
            'balance_due' => $totals['balance_due'],
            'status' => $totals['status'],
        ]);
    }

    public function issue(int $id, ?int $staffId): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE invoices SET
                status = :status,
                issued_by = :issued_by,
                issued_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'status' => 'issued',
            'issued_by' => $staffId,
        ]);
    }

    public function void(int $id): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE invoices SET status = :status WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'status' => 'void',
        ]);
    }

    public function setAmountPaid(int $id, string $amountPaid): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE invoices SET amount_paid = :amount_paid WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'amount_paid' => $amountPaid,
        ]);
    }

    public function nextNumber(int $year): int
    {
        $prefix = 'INV-' . $year . '-';
        $stmt = Database::connection()->prepare(
            'SELECT invoice_number FROM invoices
             WHERE invoice_number LIKE :prefix
             ORDER BY invoice_number DESC
             LIMIT 1'
        );
        $stmt->execute(['prefix' => $prefix . '%']);
        $last = $stmt->fetchColumn();
        if (!is_string($last) || $last === '') {
            return 1;
        }

        $parts = explode('-', $last);
        $seq = (int) end($parts);

        return $seq + 1;
    }
}
