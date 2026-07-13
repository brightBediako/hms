<?php

declare(strict_types=1);

define('HMS_ROOT', dirname(__DIR__));
require HMS_ROOT . '/vendor/autoload.php';

use App\Core\Database;
use App\Core\Env;
use App\Services\GuestService;
use App\Services\ReservationService;

Env::load(HMS_ROOT);

$pdo = Database::connection();
$room = $pdo->query(
    "SELECT r.id, rt.base_rate
     FROM rooms r
     INNER JOIN room_types rt ON rt.id = r.room_type_id
     WHERE r.status IN ('available', 'cleaning')
     ORDER BY r.id ASC
     LIMIT 1"
)->fetch(PDO::FETCH_ASSOC);

if ($room === false) {
    fwrite(STDERR, "Need an available room.\n");
    exit(1);
}

$guestService = new GuestService();
$guest = $guestService->normalizeFromInput([
    'full_name' => 'Smoke Guest ' . date('His'),
    'phone' => '+233200000099',
    'email' => 'smoke@example.com',
    'nationality' => 'Ghanaian',
]);
if (!$guest['ok']) {
    fwrite(STDERR, 'Guest normalize failed' . PHP_EOL);
    exit(1);
}

$today = date('Y-m-d');
$out = (new DateTimeImmutable($today))->modify('+1 day')->format('Y-m-d');
$service = new ReservationService();
$result = $service->create([
    'new_guest' => $guest['data'],
    'room_id' => (int) $room['id'],
    'check_in_date' => $today,
    'check_out_date' => $out,
    'check_in_time' => '16:29',
    'source' => 'walk_in',
    'adults' => 1,
    'children' => 0,
    'agreed_rate' => number_format((float) $room['base_rate'], 2, '.', ''),
    'notes' => 'Smoke reservation with times',
], null);

if (!$result['ok']) {
    fwrite(STDERR, $result['error'] . PHP_EOL);
    exit(1);
}

$row = $pdo->prepare('SELECT check_in_time, check_out_time, guest_id FROM reservations WHERE id = :id');
$row->execute(['id' => $result['id']]);
$data = $row->fetch(PDO::FETCH_ASSOC);

$inOk = str_starts_with((string) $data['check_in_time'], '16:29');
$outOk = str_starts_with((string) $data['check_out_time'], '12:00');
$guestOk = (int) $data['guest_id'] > 0;

echo 'Reservation #' . $result['id'] . PHP_EOL;
echo 'check_in_time=' . $data['check_in_time'] . ($inOk ? ' OK' : ' FAIL') . PHP_EOL;
echo 'check_out_time=' . $data['check_out_time'] . ($outOk ? ' OK' : ' FAIL') . PHP_EOL;
echo 'guest_id=' . $data['guest_id'] . ($guestOk ? ' OK' : ' FAIL') . PHP_EOL;

exit($inOk && $outOk && $guestOk ? 0 : 1);
