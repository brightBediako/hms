<?php

declare(strict_types=1);

/**
 * Smoke: create → start → resolve maintenance and confirm room status.
 * Usage: php scripts/smoke_maintenance.php
 */

define('HMS_ROOT', dirname(__DIR__));
require HMS_ROOT . '/vendor/autoload.php';

use App\Core\Database;
use App\Core\Env;
use App\Services\MaintenanceService;

Env::load(HMS_ROOT);

$pdo = Database::connection();
$room = $pdo->query(
    "SELECT id, room_number, status FROM rooms WHERE status IN ('available', 'cleaning') ORDER BY id ASC LIMIT 1"
)->fetch(PDO::FETCH_ASSOC);

if ($room === false) {
    fwrite(STDERR, "No available/cleaning room for maintenance smoke.\n");
    exit(1);
}

$svc = new MaintenanceService();
$created = $svc->create([
    'issue_title' => 'Smoke test leak',
    'description' => 'Automated smoke request',
    'priority' => 'high',
    'room_id' => (int) $room['id'],
    'assigned_to' => null,
], null);

if (!$created['ok']) {
    fwrite(STDERR, $created['error'] . PHP_EOL);
    exit(1);
}

$id = $created['id'];
echo "created=#{$id} room=#{$room['room_number']}\n";

$status = $pdo->query('SELECT status FROM rooms WHERE id = ' . (int) $room['id'])->fetchColumn();
echo "room_after_create={$status}\n";

$start = $svc->start($id, null);
echo 'start=' . ($start['ok'] ? 'ok' : $start['error']) . PHP_EOL;

$resolve = $svc->resolve($id, null);
echo 'resolve=' . ($resolve['ok'] ? 'ok' : $resolve['error']) . PHP_EOL;
if ($resolve['ok']) {
    echo 'room_released=' . ($resolve['room_released'] ? 'yes' : 'no') . PHP_EOL;
}

$status2 = $pdo->query('SELECT status FROM rooms WHERE id = ' . (int) $room['id'])->fetchColumn();
echo "room_after_resolve={$status2}\n";
echo "smoke ok\n";
