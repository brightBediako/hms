<?php

declare(strict_types=1);

/**
 * Seed demo room types (and weekend rate plans) when the table is empty.
 * Usage: php scripts/seed_room_types.php
 */

define('HMS_ROOT', dirname(__DIR__));

require HMS_ROOT . '/vendor/autoload.php';

use App\Core\Database;
use App\Core\Env;

Env::load(HMS_ROOT);

$pdo = Database::connection();

$count = (int) $pdo->query('SELECT COUNT(*) FROM room_types')->fetchColumn();
if ($count > 0) {
    echo "Room types already present ({$count}). Skipping.\n";
    exit(0);
}

$types = [
    [
        'name' => 'Deluxe King',
        'description' => 'King bed with garden or pool views.',
        'adults' => 2,
        'children' => 1,
        'base' => '450.00',
        'extra' => '80.00',
        'amenities' => json_encode(['High Speed WiFi', 'Mini Bar', 'Climate Control', 'Smart TV']),
    ],
    [
        'name' => 'Executive Suite',
        'description' => 'Spacious suite with sitting area and mountain views.',
        'adults' => 2,
        'children' => 2,
        'base' => '750.00',
        'extra' => '100.00',
        'amenities' => json_encode(['High Speed WiFi', 'Mini Bar', 'Climate Control', 'Smart TV', 'Work Desk', 'Safe']),
    ],
    [
        'name' => 'Twin Standard',
        'description' => 'Two twin beds, city view, ideal for colleagues.',
        'adults' => 2,
        'children' => 0,
        'base' => '320.00',
        'extra' => '60.00',
        'amenities' => json_encode(['High Speed WiFi', 'Climate Control', 'Smart TV']),
    ],
    [
        'name' => 'Penthouse',
        'description' => 'Top-floor panoramic suite with balcony and hot tub.',
        'adults' => 2,
        'children' => 2,
        'base' => '1200.00',
        'extra' => '150.00',
        'amenities' => json_encode(['High Speed WiFi', 'Mini Bar', 'Climate Control', 'Smart TV', 'Balcony', 'Hot Tub', 'Bathtub']),
    ],
];

$stmt = $pdo->prepare(
    'INSERT INTO room_types
        (name, description, base_capacity_adults, base_capacity_children, base_rate, extra_bed_rate, amenities)
     VALUES
        (:name, :description, :adults, :children, :base, :extra, :amenities)'
);

foreach ($types as $type) {
    $stmt->execute([
        'name' => $type['name'],
        'description' => $type['description'],
        'adults' => $type['adults'],
        'children' => $type['children'],
        'base' => $type['base'],
        'extra' => $type['extra'],
        'amenities' => $type['amenities'],
    ]);
    $typeId = (int) $pdo->lastInsertId();

    $pdo->prepare(
        'INSERT INTO rate_plans (room_type_id, name, rate, start_date, end_date, is_active)
         VALUES (:room_type_id, :name, :rate, NULL, NULL, 1)'
    )->execute([
        'room_type_id' => $typeId,
        'name' => 'Weekend Rate',
        'rate' => number_format(((float) $type['base']) * 1.15, 2, '.', ''),
    ]);
}

echo 'Seeded ' . count($types) . " room types with weekend rate plans.\n";
