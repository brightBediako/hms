<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Reservation;
use PDO;

final class ReportService
{
    public function __construct(
        private readonly Reservation $reservations = new Reservation(),
    ) {
    }

    /**
     * Live dashboard snapshot from operational tables.
     *
     * @return array{
     *   today: string,
     *   rooms: array{total: int, available: int, occupied: int, reserved: int, cleaning: int, maintenance: int, occupancy_pct: float},
     *   arrivals: list<array<string, mixed>>,
     *   departures: list<array<string, mixed>>,
     *   arrivals_count: int,
     *   departures_count: int,
     *   in_house_count: int,
     *   revenue_today: float,
     *   outstanding_balance: float,
     *   recent_reservations: list<array<string, mixed>>
     * }
     */
    public function dashboardSnapshot(?string $today = null): array
    {
        $today ??= date('Y-m-d');
        $pdo = Database::connection();

        $roomCounts = $this->roomStatusCounts();
        $total = max(0, (int) $roomCounts['total']);
        $occupied = (int) ($roomCounts['occupied'] ?? 0);
        $occupancyPct = $total > 0 ? round(($occupied / $total) * 100, 1) : 0.0;

        $arrivals = $this->reservations->arrivalsForDate($today);
        $departures = $this->reservations->departuresForDate($today);
        $inHouse = $this->reservations->inHouse();

        $revStmt = $pdo->prepare(
            'SELECT COALESCE(SUM(amount), 0) FROM payments WHERE DATE(paid_at) = :today'
        );
        $revStmt->execute(['today' => $today]);
        $revenueToday = round((float) $revStmt->fetchColumn(), 2);

        $balStmt = $pdo->query(
            "SELECT COALESCE(SUM(balance_due), 0) FROM invoices
             WHERE status IN ('issued', 'partially_paid')"
        );
        $outstanding = round((float) $balStmt->fetchColumn(), 2);

        $recent = $this->reservations->filtered([], 8);

        return [
            'today' => $today,
            'rooms' => [
                'total' => $total,
                'available' => (int) ($roomCounts['available'] ?? 0),
                'occupied' => $occupied,
                'reserved' => (int) ($roomCounts['reserved'] ?? 0),
                'cleaning' => (int) ($roomCounts['cleaning'] ?? 0),
                'maintenance' => (int) ($roomCounts['maintenance'] ?? 0),
                'occupancy_pct' => $occupancyPct,
            ],
            'arrivals' => array_slice($arrivals, 0, 8),
            'departures' => array_slice($departures, 0, 8),
            'arrivals_count' => count($arrivals),
            'departures_count' => count($departures),
            'in_house_count' => count($inHouse),
            'revenue_today' => $revenueToday,
            'outstanding_balance' => $outstanding,
            'recent_reservations' => $recent,
        ];
    }

    /**
     * @return array{total: int, available?: int, occupied?: int, reserved?: int, cleaning?: int, maintenance?: int}
     */
    public function roomStatusCounts(): array
    {
        $stmt = Database::connection()->query(
            'SELECT status, COUNT(*) AS cnt FROM rooms GROUP BY status'
        );
        $counts = ['total' => 0];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $status = (string) $row['status'];
            $cnt = (int) $row['cnt'];
            $counts[$status] = $cnt;
            $counts['total'] += $cnt;
        }

        return $counts;
    }

    /**
     * @return array{
     *   from: string,
     *   to: string,
     *   days: int,
     *   total_rooms: int,
     *   room_nights_available: int,
     *   room_nights_occupied: float,
     *   occupancy_pct: float,
     *   by_status: list<array{status: string, count: int}>
     * }
     */
    public function occupancyReport(string $from, string $to): array
    {
        [$from, $to] = $this->normalizeRange($from, $to);
        $pdo = Database::connection();

        $days = max(1, (int) ((strtotime($to) - strtotime($from)) / 86400) + 1);
        $totalRooms = (int) $pdo->query('SELECT COUNT(*) FROM rooms')->fetchColumn();
        $availableNights = $totalRooms * $days;

        $stmt = $pdo->prepare(
            "SELECT COALESCE(SUM(
                GREATEST(
                    0,
                    DATEDIFF(
                        LEAST(check_out_date, DATE_ADD(:to1, INTERVAL 1 DAY)),
                        GREATEST(check_in_date, :from1)
                    )
                )
             ), 0) AS nights
             FROM reservations
             WHERE status IN ('booked', 'checked_in', 'checked_out')
               AND check_in_date < DATE_ADD(:to2, INTERVAL 1 DAY)
               AND check_out_date > :from2"
        );
        $stmt->execute([
            'from1' => $from,
            'to1' => $to,
            'from2' => $from,
            'to2' => $to,
        ]);
        $occupiedNights = round((float) $stmt->fetchColumn(), 1);
        $pct = $availableNights > 0 ? round(($occupiedNights / $availableNights) * 100, 1) : 0.0;

        $statusStmt = $pdo->prepare(
            'SELECT status, COUNT(*) AS count
             FROM reservations
             WHERE check_in_date <= :to AND check_out_date >= :from
             GROUP BY status
             ORDER BY status'
        );
        $statusStmt->execute(['from' => $from, 'to' => $to]);
        /** @var list<array{status: string, count: int}> $byStatus */
        $byStatus = $statusStmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'from' => $from,
            'to' => $to,
            'days' => $days,
            'total_rooms' => $totalRooms,
            'room_nights_available' => $availableNights,
            'room_nights_occupied' => $occupiedNights,
            'occupancy_pct' => $pct,
            'by_status' => $byStatus,
        ];
    }

    /**
     * @return array{
     *   from: string,
     *   to: string,
     *   total: float,
     *   by_method: list<array{method: string, total: float, count: int}>,
     *   payments: list<array<string, mixed>>
     * }
     */
    public function revenueReport(string $from, string $to): array
    {
        [$from, $to] = $this->normalizeRange($from, $to);
        $pdo = Database::connection();

        $sumStmt = $pdo->prepare(
            'SELECT COALESCE(SUM(amount), 0) FROM payments
             WHERE DATE(paid_at) BETWEEN :from AND :to'
        );
        $sumStmt->execute(['from' => $from, 'to' => $to]);
        $total = round((float) $sumStmt->fetchColumn(), 2);

        $methodStmt = $pdo->prepare(
            'SELECT method, COALESCE(SUM(amount), 0) AS total, COUNT(*) AS count
             FROM payments
             WHERE DATE(paid_at) BETWEEN :from AND :to
             GROUP BY method
             ORDER BY total DESC'
        );
        $methodStmt->execute(['from' => $from, 'to' => $to]);
        $byMethod = [];
        while ($row = $methodStmt->fetch(PDO::FETCH_ASSOC)) {
            $byMethod[] = [
                'method' => (string) $row['method'],
                'total' => round((float) $row['total'], 2),
                'count' => (int) $row['count'],
            ];
        }

        $listStmt = $pdo->prepare(
            'SELECT p.*, i.invoice_number, g.full_name AS guest_name
             FROM payments p
             INNER JOIN invoices i ON i.id = p.invoice_id
             INNER JOIN guests g ON g.id = i.guest_id
             WHERE DATE(p.paid_at) BETWEEN :from AND :to
             ORDER BY p.paid_at DESC
             LIMIT 200'
        );
        $listStmt->execute(['from' => $from, 'to' => $to]);
        /** @var list<array<string, mixed>> $payments */
        $payments = $listStmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'from' => $from,
            'to' => $to,
            'total' => $total,
            'by_method' => $byMethod,
            'payments' => $payments,
        ];
    }

    /**
     * @return array{
     *   from: string,
     *   to: string,
     *   total: int,
     *   by_status: list<array{status: string, count: int}>,
     *   reservations: list<array<string, mixed>>
     * }
     */
    public function reservationsReport(string $from, string $to): array
    {
        [$from, $to] = $this->normalizeRange($from, $to);
        $pdo = Database::connection();

        $countStmt = $pdo->prepare(
            'SELECT COUNT(*) FROM reservations
             WHERE check_in_date BETWEEN :from AND :to'
        );
        $countStmt->execute(['from' => $from, 'to' => $to]);
        $total = (int) $countStmt->fetchColumn();

        $statusStmt = $pdo->prepare(
            'SELECT status, COUNT(*) AS count
             FROM reservations
             WHERE check_in_date BETWEEN :from AND :to
             GROUP BY status
             ORDER BY status'
        );
        $statusStmt->execute(['from' => $from, 'to' => $to]);
        /** @var list<array{status: string, count: int}> $byStatus */
        $byStatus = $statusStmt->fetchAll(PDO::FETCH_ASSOC);

        $listStmt = $pdo->prepare(
            'SELECT r.*,
                    g.full_name AS guest_name,
                    rm.room_number,
                    rt.name AS room_type_name
             FROM reservations r
             INNER JOIN guests g ON g.id = r.guest_id
             INNER JOIN rooms rm ON rm.id = r.room_id
             INNER JOIN room_types rt ON rt.id = rm.room_type_id
             WHERE r.check_in_date BETWEEN :from AND :to
             ORDER BY r.check_in_date DESC, r.id DESC
             LIMIT 200'
        );
        $listStmt->execute(['from' => $from, 'to' => $to]);
        /** @var list<array<string, mixed>> $list */
        $list = $listStmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'from' => $from,
            'to' => $to,
            'total' => $total,
            'by_status' => $byStatus,
            'reservations' => $list,
        ];
    }

    /**
     * @return array{
     *   from: string,
     *   to: string,
     *   total_guests: int,
     *   new_guests: int,
     *   guests: list<array<string, mixed>>
     * }
     */
    public function guestsReport(string $from, string $to): array
    {
        [$from, $to] = $this->normalizeRange($from, $to);
        $pdo = Database::connection();

        $total = (int) $pdo->query('SELECT COUNT(*) FROM guests')->fetchColumn();

        $newStmt = $pdo->prepare(
            'SELECT COUNT(*) FROM guests WHERE DATE(created_at) BETWEEN :from AND :to'
        );
        $newStmt->execute(['from' => $from, 'to' => $to]);
        $newGuests = (int) $newStmt->fetchColumn();

        $listStmt = $pdo->prepare(
            'SELECT g.*,
                    (SELECT COUNT(*) FROM reservations r WHERE r.guest_id = g.id) AS stays_count
             FROM guests g
             WHERE DATE(g.created_at) BETWEEN :from AND :to
             ORDER BY g.created_at DESC
             LIMIT 200'
        );
        $listStmt->execute(['from' => $from, 'to' => $to]);
        /** @var list<array<string, mixed>> $guests */
        $guests = $listStmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'from' => $from,
            'to' => $to,
            'total_guests' => $total,
            'new_guests' => $newGuests,
            'guests' => $guests,
        ];
    }

    /**
     * @return array{
     *   from: string,
     *   to: string,
     *   total: float,
     *   by_category: list<array{category: string, total: float, count: int}>,
     *   expenses: list<array<string, mixed>>
     * }
     */
    public function expensesReport(string $from, string $to): array
    {
        [$from, $to] = $this->normalizeRange($from, $to);
        $pdo = Database::connection();

        $sumStmt = $pdo->prepare(
            'SELECT COALESCE(SUM(amount), 0) FROM expenses
             WHERE expense_date BETWEEN :from AND :to'
        );
        $sumStmt->execute(['from' => $from, 'to' => $to]);
        $total = round((float) $sumStmt->fetchColumn(), 2);

        $catStmt = $pdo->prepare(
            'SELECT c.name AS category, COALESCE(SUM(e.amount), 0) AS total, COUNT(*) AS count
             FROM expenses e
             INNER JOIN expense_categories c ON c.id = e.category_id
             WHERE e.expense_date BETWEEN :from AND :to
             GROUP BY c.id, c.name
             ORDER BY total DESC'
        );
        $catStmt->execute(['from' => $from, 'to' => $to]);
        $byCategory = [];
        while ($row = $catStmt->fetch(PDO::FETCH_ASSOC)) {
            $byCategory[] = [
                'category' => (string) $row['category'],
                'total' => round((float) $row['total'], 2),
                'count' => (int) $row['count'],
            ];
        }

        $listStmt = $pdo->prepare(
            'SELECT e.*, c.name AS category_name
             FROM expenses e
             INNER JOIN expense_categories c ON c.id = e.category_id
             WHERE e.expense_date BETWEEN :from AND :to
             ORDER BY e.expense_date DESC, e.id DESC
             LIMIT 200'
        );
        $listStmt->execute(['from' => $from, 'to' => $to]);
        /** @var list<array<string, mixed>> $expenses */
        $expenses = $listStmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'from' => $from,
            'to' => $to,
            'total' => $total,
            'by_category' => $byCategory,
            'expenses' => $expenses,
        ];
    }

    /**
     * @return array{
     *   from: string,
     *   to: string,
     *   revenue: float,
     *   expenses: float,
     *   profit: float,
     *   outstanding: float
     * }
     */
    public function profitSummary(string $from, string $to): array
    {
        [$from, $to] = $this->normalizeRange($from, $to);
        $revenue = $this->revenueReport($from, $to)['total'];
        $expenses = $this->expensesReport($from, $to)['total'];

        $balStmt = Database::connection()->query(
            "SELECT COALESCE(SUM(balance_due), 0) FROM invoices
             WHERE status IN ('issued', 'partially_paid')"
        );
        $outstanding = round((float) $balStmt->fetchColumn(), 2);

        return [
            'from' => $from,
            'to' => $to,
            'revenue' => $revenue,
            'expenses' => $expenses,
            'profit' => round($revenue - $expenses, 2),
            'outstanding' => $outstanding,
        ];
    }

    public function labelForReservationStatus(string $status): string
    {
        return match ($status) {
            'booked' => 'Booked',
            'checked_in' => 'Checked in',
            'checked_out' => 'Checked out',
            'cancelled' => 'Cancelled',
            'no_show' => 'No show',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    public function labelForPaymentMethod(string $method): string
    {
        return match ($method) {
            'cash' => 'Cash',
            'mobile_money' => 'Mobile money',
            'card' => 'Card',
            'bank_transfer' => 'Bank transfer',
            'other' => 'Other',
            default => ucfirst(str_replace('_', ' ', $method)),
        };
    }

    /**
     * @return array{0: string, 1: string}
     */
    public function normalizeRange(string $from, string $to): array
    {
        $from = trim($from);
        $to = trim($to);
        if ($from === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
            $from = date('Y-m-01');
        }
        if ($to === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            $to = date('Y-m-d');
        }
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }

    public function defaultFrom(): string
    {
        return date('Y-m-01');
    }

    public function defaultTo(): string
    {
        return date('Y-m-d');
    }
}
