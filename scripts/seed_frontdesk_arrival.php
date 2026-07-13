<?php

declare(strict_types=1);

define('HMS_ROOT', dirname(__DIR__));
require HMS_ROOT . '/vendor/autoload.php';

use App\Core\Database;
use App\Core\Env;
use App\Services\ReservationService;

Env::load(HMS_ROOT);

$pdo = Database::connection();
$guestId = (int) $pdo->query('SELECT id FROM guests ORDER BY id ASC LIMIT 1')->fetchColumn();
$room = $pdo->query(
    "SELECT r.id, rt.base_rate
     FROM rooms r
     INNER JOIN room_types rt ON rt.id = r.room_type_id
     WHERE r.status IN ('available', 'cleaning')
     ORDER BY r.id ASC
     LIMIT 1"
)->fetch(PDO::FETCH_ASSOC);

if ($guestId < 1 || $room === false) {
    fwrite(STDERR, "Need a guest and an available/cleaning room.\n");
    exit(1);
}

$today = date('Y-m-d');
$out = (new DateTimeImmutable($today))->modify('+3 days')->format('Y-m-d');
$result = (new ReservationService())->create([
    'guest_id' => $guestId,
    'room_id' => (int) $room['id'],
    'check_in_date' => $today,
    'check_out_date' => $out,
    'source' => 'advance',
    'adults' => 2,
    'children' => 0,
    'agreed_rate' => number_format((float) $room['base_rate'], 2, '.', ''),
    'notes' => 'Demo arrival for Front Desk',
], null);

if (!$result['ok']) {
    fwrite(STDERR, $result['error'] . PHP_EOL);
    exit(1);
}

echo 'Created booked arrival #' . $result['id'] . PHP_EOL;
