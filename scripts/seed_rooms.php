<?php

declare(strict_types=1);

/**
 * Seed demo rooms when inventory is empty (requires room types).
 * Usage: php scripts/seed_rooms.php
 */

define('HMS_ROOT', dirname(__DIR__));

require HMS_ROOT . '/vendor/autoload.php';

use App\Core\Database;
use App\Core\Env;

Env::load(HMS_ROOT);

$pdo = Database::connection();

$typeCount = (int) $pdo->query('SELECT COUNT(*) FROM room_types')->fetchColumn();
if ($typeCount === 0) {
    fwrite(STDERR, "No room types found. Run scripts/seed_room_types.php first.\n");
    exit(1);
}

$roomCount = (int) $pdo->query('SELECT COUNT(*) FROM rooms')->fetchColumn();
if ($roomCount > 0) {
    echo "Rooms already present ({$roomCount}). Skipping.\n";
    exit(0);
}

$types = $pdo->query('SELECT id, name FROM room_types ORDER BY id ASC')->fetchAll(PDO::FETCH_ASSOC);
$byName = [];
foreach ($types as $type) {
    $byName[(string) $type['name']] = (int) $type['id'];
}

$fallbackId = (int) $types[0]['id'];

$demo = [
    ['101', 'Deluxe King', 'Floor 1 - Garden', 'occupied', 'Guest in-house'],
    ['102', 'Deluxe King', 'Floor 1 - Garden', 'available', null],
    ['103', 'Twin Standard', 'Floor 1 - Garden', 'reserved', 'Check-in at 14:00'],
    ['104', 'Twin Standard', 'Floor 1 - Garden', 'occupied', null],
    ['201', 'Executive Suite', 'Floor 2 - Executive', 'cleaning', 'Est. finish 45m'],
    ['204', 'Deluxe King', 'Floor 2 - Executive', 'maintenance', 'AC repair needed'],
    ['205', 'Executive Suite', 'Floor 2 - Executive', 'available', null],
    ['305', 'Penthouse', 'Floor 3 - Penthouse', 'available', null],
];

$insert = $pdo->prepare(
    'INSERT INTO rooms (room_type_id, room_number, floor, status, notes)
     VALUES (:room_type_id, :room_number, :floor, :status, :notes)'
);
$log = $pdo->prepare(
    'INSERT INTO room_status_log (room_id, old_status, new_status, changed_by, reason)
     VALUES (:room_id, NULL, :new_status, NULL, :reason)'
);

foreach ($demo as [$number, $typeName, $floor, $status, $notes]) {
    $typeId = $byName[$typeName] ?? $fallbackId;
    $insert->execute([
        'room_type_id' => $typeId,
        'room_number' => $number,
        'floor' => $floor,
        'status' => $status,
        'notes' => $notes,
    ]);
    $roomId = (int) $pdo->lastInsertId();
    $log->execute([
        'room_id' => $roomId,
        'new_status' => $status,
        'reason' => 'Seeded inventory',
    ]);
}

echo 'Seeded ' . count($demo) . " rooms.\n";
