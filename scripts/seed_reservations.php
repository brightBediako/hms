<?php

declare(strict_types=1);

/**
 * Seed demo reservations when empty (requires guests + rooms).
 * Usage: php scripts/seed_reservations.php
 */

define('HMS_ROOT', dirname(__DIR__));

require HMS_ROOT . '/vendor/autoload.php';

use App\Core\Database;
use App\Core\Env;
use App\Services\ReservationService;

Env::load(HMS_ROOT);

$pdo = Database::connection();

$guestCount = (int) $pdo->query('SELECT COUNT(*) FROM guests')->fetchColumn();
$roomCount = (int) $pdo->query('SELECT COUNT(*) FROM rooms')->fetchColumn();
if ($guestCount === 0 || $roomCount === 0) {
    fwrite(STDERR, "Need guests and rooms first (seed_guests.php / seed_rooms.php).\n");
    exit(1);
}

$resCount = (int) $pdo->query('SELECT COUNT(*) FROM reservations')->fetchColumn();
if ($resCount > 0) {
    echo "Reservations already present ({$resCount}). Skipping.\n";
    exit(0);
}

$guests = $pdo->query('SELECT id FROM guests ORDER BY id ASC')->fetchAll(PDO::FETCH_COLUMN);
$rooms = $pdo->query(
    "SELECT r.id, rt.base_rate
     FROM rooms r
     INNER JOIN room_types rt ON rt.id = r.room_type_id
     WHERE r.status IN ('available','cleaning')
     ORDER BY r.room_number ASC"
)->fetchAll(PDO::FETCH_ASSOC);

if (count($guests) < 3 || count($rooms) < 3) {
    fwrite(STDERR, "Need at least 3 guests and 3 bookable rooms.\n");
    exit(1);
}

$service = new ReservationService();
$today = new DateTimeImmutable('today');

$plans = [
    [
        'guest_id' => (int) $guests[0],
        'room_id' => (int) $rooms[0]['id'],
        'check_in_date' => $today->modify('+1 day')->format('Y-m-d'),
        'check_out_date' => $today->modify('+4 days')->format('Y-m-d'),
        'source' => 'advance',
        'adults' => 2,
        'children' => 0,
        'agreed_rate' => number_format((float) $rooms[0]['base_rate'], 2, '.', ''),
        'notes' => 'Early arrival preferred',
    ],
    [
        'guest_id' => (int) $guests[1],
        'room_id' => (int) $rooms[1]['id'],
        'check_in_date' => $today->modify('+3 days')->format('Y-m-d'),
        'check_out_date' => $today->modify('+6 days')->format('Y-m-d'),
        'source' => 'phone',
        'adults' => 1,
        'children' => 1,
        'agreed_rate' => number_format((float) $rooms[1]['base_rate'], 2, '.', ''),
        'notes' => null,
    ],
    [
        'guest_id' => (int) $guests[2],
        'room_id' => (int) $rooms[2]['id'],
        'check_in_date' => $today->format('Y-m-d'),
        'check_out_date' => $today->modify('+2 days')->format('Y-m-d'),
        'source' => 'walk_in',
        'adults' => 2,
        'children' => 0,
        'agreed_rate' => number_format((float) $rooms[2]['base_rate'], 2, '.', ''),
        'notes' => 'Walk-in demo booking',
    ],
];

$created = 0;
foreach ($plans as $plan) {
    $result = $service->create($plan, null);
    if ($result['ok']) {
        $created++;
        echo 'Created reservation #' . $result['id'] . "\n";
    } else {
        echo 'Skipped: ' . $result['error'] . "\n";
    }
}

echo "Seeded {$created} reservations.\n";
