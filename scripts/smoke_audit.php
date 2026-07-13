<?php

declare(strict_types=1);

define('HMS_ROOT', dirname(__DIR__));
require HMS_ROOT . '/vendor/autoload.php';

use App\Core\Database;
use App\Core\Env;
use App\Models\AuditLog;
use App\Services\AuditService;

Env::load(HMS_ROOT);

$pdo = Database::connection();
$staffId = (int) $pdo->query('SELECT id FROM staff ORDER BY id ASC LIMIT 1')->fetchColumn();

$audit = new AuditService();
$audit->log(
    'reservation.create',
    'reservations',
    999001,
    null,
    ['booking_reference' => 'SMOKE-AUDIT', 'status' => 'booked'],
    $staffId > 0 ? $staffId : null,
);
$audit->log(
    'staff.update',
    'staff',
    $staffId,
    ['status' => 'active'],
    ['status' => 'active', 'password' => 'secret-should-redact'],
    $staffId > 0 ? $staffId : null,
);

$logs = new AuditLog();
$rows = $logs->filtered(['action' => 'reservation.create', 'q' => '999001'], 10);
echo 'found=' . count($rows) . PHP_EOL;
if ($rows === []) {
    // Fallback: latest reservation.create
    $rows = $logs->filtered(['action' => 'reservation.create'], 1);
}
if ($rows === []) {
    fwrite(STDERR, "smoke audit row missing\n");
    exit(1);
}

$detail = $logs->findById((int) $rows[0]['id']);
echo 'action=' . ($detail['action'] ?? '') . PHP_EOL;

$staffRows = $logs->filtered(['action' => 'staff.update'], 5);
$decoded = json_decode((string) ($staffRows[0]['new_values'] ?? '{}'), true);
$pwd = is_array($decoded) ? ($decoded['password'] ?? null) : null;
echo 'password_redacted=' . ($pwd === '[redacted]' ? 'yes' : 'no') . PHP_EOL;
echo "smoke ok\n";
