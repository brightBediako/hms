<?php

declare(strict_types=1);

/**
 * Record a demo partial + settling payment against the first payable invoice.
 * Usage: php scripts/seed_payment.php
 */

define('HMS_ROOT', dirname(__DIR__));
require HMS_ROOT . '/vendor/autoload.php';

use App\Core\Database;
use App\Core\Env;
use App\Services\PaymentService;

Env::load(HMS_ROOT);

$pdo = Database::connection();
$count = (int) $pdo->query('SELECT COUNT(*) FROM payments')->fetchColumn();
if ($count > 0) {
    echo "Payments already present ({$count}). Skipping.\n";
    exit(0);
}

$invoice = $pdo->query(
    "SELECT id, balance_due FROM invoices
     WHERE status IN ('issued', 'partially_paid') AND balance_due > 0
     ORDER BY id ASC LIMIT 1"
)->fetch(PDO::FETCH_ASSOC);

if ($invoice === false) {
    fwrite(STDERR, "No payable invoice found. Issue an invoice first.\n");
    exit(1);
}

$svc = new PaymentService();
$balance = (float) $invoice['balance_due'];
$partial = round(min(500, max(1, $balance * 0.3)), 2);

$r1 = $svc->record([
    'invoice_id' => (int) $invoice['id'],
    'method' => 'mobile_money',
    'amount' => number_format($partial, 2, '.', ''),
    'reference_number' => 'MOMO-DEMO-001',
    'notes' => 'Partial payment seed',
], null);

if (!$r1['ok']) {
    fwrite(STDERR, $r1['error'] . PHP_EOL);
    exit(1);
}

echo "Partial payment #{$r1['id']} of {$partial}\n";

$remaining = $pdo->prepare('SELECT balance_due, status FROM invoices WHERE id = :id');
$remaining->execute(['id' => $invoice['id']]);
$row = $remaining->fetch(PDO::FETCH_ASSOC);
echo 'status=' . $row['status'] . ' balance=' . $row['balance_due'] . PHP_EOL;
