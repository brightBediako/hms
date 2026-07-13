<?php

declare(strict_types=1);

/**
 * Seed a couple of demo expenses.
 * Usage: php scripts/seed_expenses.php
 */

define('HMS_ROOT', dirname(__DIR__));
require HMS_ROOT . '/vendor/autoload.php';

use App\Core\Database;
use App\Core\Env;
use App\Services\ExpenseService;

Env::load(HMS_ROOT);

$pdo = Database::connection();
$cat = $pdo->query('SELECT id FROM expense_categories ORDER BY id ASC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
$staff = $pdo->query('SELECT id FROM staff ORDER BY id ASC LIMIT 1')->fetch(PDO::FETCH_ASSOC);

if ($cat === false) {
    fwrite(STDERR, "No expense categories. Run seed SQL first.\n");
    exit(1);
}

$svc = new ExpenseService();
$samples = [
    [
        'category_id' => (int) $cat['id'],
        'description' => 'Electricity bill — demo',
        'amount' => '450.00',
        'expense_date' => date('Y-m-d', strtotime('-3 days')),
    ],
    [
        'category_id' => (int) $cat['id'],
        'description' => 'Plumbing supplies — demo',
        'amount' => '85.50',
        'expense_date' => date('Y-m-d', strtotime('-1 day')),
    ],
];

foreach ($samples as $row) {
    $result = $svc->record($row, $staff ? (int) $staff['id'] : null);
    if (!$result['ok']) {
        fwrite(STDERR, $result['error'] . PHP_EOL);
        exit(1);
    }
    echo "expense=#{$result['id']} {$row['description']}\n";
}

echo "seed ok\n";
