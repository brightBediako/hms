<?php

declare(strict_types=1);

define('HMS_ROOT', dirname(__DIR__));
require HMS_ROOT . '/vendor/autoload.php';

use App\Core\Database;
use App\Core\Env;
use App\Services\NotificationService;

Env::load(HMS_ROOT);

$pdo = Database::connection();
$staffId = (int) $pdo->query('SELECT id FROM staff WHERE status = \'active\' ORDER BY id ASC LIMIT 1')->fetchColumn();
if ($staffId <= 0) {
    fwrite(STDERR, "No active staff\n");
    exit(1);
}

$svc = new NotificationService();
$created = $svc->notifyStaff(
    $staffId,
    'Smoke notification',
    'Feature 17 smoke test message',
    NotificationService::TYPE_SYSTEM,
);

if (!$created['ok']) {
    fwrite(STDERR, $created['error'] . PHP_EOL);
    exit(1);
}

echo "notif=#{$created['id']} staff=#{$staffId}\n";
echo 'unread=' . $svc->unreadCount($staffId) . PHP_EOL;

$fanout = $svc->notifyByPermission(
    \Permission::DASHBOARD_VIEW,
    'System ping',
    'Permission fan-out smoke',
    NotificationService::TYPE_SYSTEM,
    null,
    null,
    null,
);
echo "fanout={$fanout}\n";

$marked = $svc->markAllRead($staffId);
echo "marked_read={$marked}\n";
echo 'unread_after=' . $svc->unreadCount($staffId) . PHP_EOL;
echo "smoke ok\n";
