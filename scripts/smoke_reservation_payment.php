<?php

declare(strict_types=1);

define('HMS_ROOT', dirname(__DIR__));
require HMS_ROOT . '/vendor/autoload.php';

use App\Core\Database;
use App\Core\Env;
use App\Services\AvailabilityService;
use App\Services\GuestService;
use App\Services\PaymentService;
use App\Services\ReservationService;

Env::load(HMS_ROOT);

$pdo = Database::connection();
$today = date('Y-m-d');
$out = (new DateTimeImmutable($today))->modify('+2 days')->format('Y-m-d');

$rooms = (new AvailabilityService())->availableRooms($today, $out);
if ($rooms === []) {
    fwrite(STDERR, "Need an available room for {$today} → {$out}.\n");
    exit(1);
}
$room = $rooms[0];

$guest = (new GuestService())->normalizeFromInput([
    'full_name' => 'Pay Guest ' . date('His'),
    'phone' => '+233200000088',
]);
if (!$guest['ok']) {
    fwrite(STDERR, "Guest normalize failed\n");
    exit(1);
}

$rate = number_format((float) $room['base_rate'], 2, '.', '');

$created = (new ReservationService())->create([
    'new_guest' => $guest['data'],
    'room_id' => (int) $room['id'],
    'check_in_date' => $today,
    'check_out_date' => $out,
    'check_in_time' => '16:00',
    'source' => 'walk_in',
    'adults' => 1,
    'children' => 0,
    'agreed_rate' => $rate,
    'notes' => 'Payment smoke',
], null);

if (!$created['ok']) {
    fwrite(STDERR, $created['error'] . PHP_EOL);
    exit(1);
}

$reservationId = $created['id'];
$half = round(((float) $rate) * 2 * 0.5, 2); // 2 nights, 50% without tax

$paid = (new PaymentService())->collectForReservation($reservationId, [
    'amount' => $half,
    'method' => 'cash',
    'include_tax' => false,
    'notes' => 'Partial at booking smoke',
], null);

if (!$paid['ok']) {
    fwrite(STDERR, $paid['error'] . PHP_EOL);
    exit(1);
}

$stmt = $pdo->prepare('SELECT status, total_amount, amount_paid, balance_due FROM invoices WHERE id = :id');
$stmt->execute(['id' => $paid['invoice_id']]);
$inv = $stmt->fetch(PDO::FETCH_ASSOC);

$okPartial = $inv
    && (string) $inv['status'] === 'partially_paid'
    && abs((float) $inv['amount_paid'] - $half) < 0.02
    && (float) $inv['balance_due'] > 0;

echo 'Reservation #' . $reservationId . PHP_EOL;
echo 'Invoice #' . $paid['invoice_id'] . ' status=' . $inv['status'] . PHP_EOL;
echo 'paid=' . $inv['amount_paid'] . ' due=' . $inv['balance_due'] . ($okPartial ? ' OK' : ' FAIL') . PHP_EOL;

exit($okPartial ? 0 : 1);
