<?php

declare(strict_types=1);

/**
 * Seed a draft invoice from the first checked-out (or checked-in) reservation.
 * Usage: php scripts/seed_invoice.php
 */

define('HMS_ROOT', dirname(__DIR__));
require HMS_ROOT . '/vendor/autoload.php';

use App\Core\Database;
use App\Core\Env;
use App\Services\BillingService;

Env::load(HMS_ROOT);

$pdo = Database::connection();
$count = (int) $pdo->query('SELECT COUNT(*) FROM invoices')->fetchColumn();
if ($count > 0) {
    echo "Invoices already present ({$count}). Skipping.\n";
    exit(0);
}

$row = $pdo->query(
    "SELECT id FROM reservations WHERE status IN ('checked_out', 'checked_in') ORDER BY FIELD(status, 'checked_out', 'checked_in'), id ASC LIMIT 1"
)->fetch(PDO::FETCH_ASSOC);

if ($row === false) {
    fwrite(STDERR, "No checked-in/out reservation found. Run Front Desk check-in/out first.\n");
    exit(1);
}

$result = (new BillingService())->generateFromReservation((int) $row['id'], null, true);
if (!$result['ok']) {
    fwrite(STDERR, $result['error'] . PHP_EOL);
    exit(1);
}

echo 'Created draft invoice #' . $result['id'] . PHP_EOL;
