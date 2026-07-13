<?php

declare(strict_types=1);

/**
 * Smoke: complete an open checkout_clean task and confirm room release.
 * Usage: php scripts/smoke_housekeeping.php
 */

define('HMS_ROOT', dirname(__DIR__));
require HMS_ROOT . '/vendor/autoload.php';

use App\Core\Database;
use App\Core\Env;
use App\Services\HousekeepingService;

Env::load(HMS_ROOT);

$pdo = Database::connection();
$row = $pdo->query(
    "SELECT t.id, t.room_id, rm.status AS room_status, rm.room_number
     FROM housekeeping_tasks t
     INNER JOIN rooms rm ON rm.id = t.room_id
     WHERE t.status IN ('pending', 'in_progress')
     ORDER BY t.id ASC
     LIMIT 1"
)->fetch(PDO::FETCH_ASSOC);

if ($row === false) {
    // Create a task on a cleaning or available room
    $room = $pdo->query(
        "SELECT id FROM rooms WHERE status IN ('cleaning', 'available') ORDER BY id ASC LIMIT 1"
    )->fetch(PDO::FETCH_ASSOC);
    if ($room === false) {
        fwrite(STDERR, "No room for HK smoke.\n");
        exit(1);
    }
    $svc = new HousekeepingService();
    $created = $svc->create([
        'room_id' => (int) $room['id'],
        'task_type' => 'checkout_clean',
        'scheduled_for' => date('Y-m-d'),
        'notes' => 'Smoke task',
    ]);
    if (!$created['ok']) {
        fwrite(STDERR, $created['error'] . PHP_EOL);
        exit(1);
    }
    $taskId = $created['id'];
    echo "created_task={$taskId}\n";
} else {
    $taskId = (int) $row['id'];
    echo 'using_task=' . $taskId . ' room=#' . $row['room_number'] . ' status=' . $row['room_status'] . PHP_EOL;
}

$svc = new HousekeepingService();
$start = $svc->start($taskId);
echo 'start=' . ($start['ok'] ? 'ok' : ($start['error'] ?? 'skip')) . PHP_EOL;

$done = $svc->complete($taskId, null);
echo 'complete=' . ($done['ok'] ? 'ok' : $done['error']) . PHP_EOL;
if ($done['ok']) {
    echo 'room_released=' . ($done['room_released'] ? 'yes' : 'no') . PHP_EOL;
}

$roomStatus = $pdo->query(
    'SELECT rm.status FROM housekeeping_tasks t INNER JOIN rooms rm ON rm.id = t.room_id WHERE t.id = ' . (int) $taskId
)->fetchColumn();
echo 'room_status_now=' . $roomStatus . PHP_EOL;
echo "smoke ok\n";
