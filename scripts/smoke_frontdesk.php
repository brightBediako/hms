<?php

declare(strict_types=1);

/**
 * Smoke-test front desk check-in → checkout lifecycle (CLI).
 * Usage: php scripts/smoke_frontdesk.php
 */

define('HMS_ROOT', dirname(__DIR__));
require HMS_ROOT . '/vendor/autoload.php';

use App\Core\Database;
use App\Core\Env;
use App\Services\FrontDeskService;

Env::load(HMS_ROOT);

$pdo = Database::connection();
$row = $pdo->query(
    "SELECT id FROM reservations WHERE status = 'booked' AND check_in_date <= CURDATE() ORDER BY id ASC LIMIT 1"
)->fetch(PDO::FETCH_ASSOC);

if ($row === false) {
    // Fall back to any booked reservation
    $row = $pdo->query("SELECT id FROM reservations WHERE status = 'booked' ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
}

if ($row === false) {
    fwrite(STDERR, "No booked reservation to check in.\n");
    exit(1);
}

$id = (int) $row['id'];
$svc = new FrontDeskService();

$in = $svc->checkIn($id, null, null);
echo 'check_in: ' . ($in['ok'] ? 'ok' : $in['error']) . PHP_EOL;
if (!$in['ok']) {
    exit(1);
}

$room = $pdo->query(
    'SELECT rm.status FROM reservations r INNER JOIN rooms rm ON rm.id = r.room_id WHERE r.id = ' . $id
)->fetchColumn();
echo 'room_after_checkin: ' . $room . PHP_EOL;

$out = $svc->checkOut($id, null);
echo 'check_out: ' . ($out['ok'] ? 'ok' : $out['error']) . PHP_EOL;

$room2 = $pdo->query(
    'SELECT rm.status FROM reservations r INNER JOIN rooms rm ON rm.id = r.room_id WHERE r.id = ' . $id
)->fetchColumn();
$hk = (int) $pdo->query(
    "SELECT COUNT(*) FROM housekeeping_tasks WHERE task_type = 'checkout_clean' AND status = 'pending'"
)->fetchColumn();

echo 'room_after_checkout: ' . $room2 . PHP_EOL;
echo 'pending_hk_tasks: ' . $hk . PHP_EOL;
echo "smoke ok\n";
