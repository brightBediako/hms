<?php

declare(strict_types=1);

/**
 * Seed demo guests when the table is empty.
 * Usage: php scripts/seed_guests.php
 */

define('HMS_ROOT', dirname(__DIR__));

require HMS_ROOT . '/vendor/autoload.php';

use App\Core\Database;
use App\Core\Env;

Env::load(HMS_ROOT);

$pdo = Database::connection();
$count = (int) $pdo->query('SELECT COUNT(*) FROM guests')->fetchColumn();
if ($count > 0) {
    echo "Guests already present ({$count}). Skipping.\n";
    exit(0);
}

$demo = [
    ['Ama Mensah', 'ama.mensah@example.com', '+233241110001', 'national_id', 'GHA-4589210', 'Ghanaian', 'Accra, Ghana', 'Prefers high floor'],
    ['Kwesi Boateng', 'kwesi.b@example.com', '+233241110002', 'passport', 'G1234567', 'Ghanaian', 'Kumasi', null],
    ['Fatima Alhassan', 'fatima.a@example.com', '+233241110003', 'national_id', 'GHA-9923411', 'Ghanaian', 'Tamale', 'VIP — late checkout when possible'],
    ['James Okoro', 'james.okoro@example.com', '+2348012345678', 'passport', 'A0987654', 'Nigerian', 'Lagos', null],
    ['Sarah Addo', null, '+233209998877', 'drivers_license', 'DL-445566', 'Ghanaian', 'Tema', 'Travels with spouse'],
    ['Michael Asante', 'm.asante@example.com', '+233244556677', 'passport', 'G7654321', 'Ghanaian', 'Cape Coast', null],
];

$stmt = $pdo->prepare(
    'INSERT INTO guests (full_name, email, phone, id_type, id_number, nationality, address, notes)
     VALUES (:full_name, :email, :phone, :id_type, :id_number, :nationality, :address, :notes)'
);

foreach ($demo as [$name, $email, $phone, $idType, $idNumber, $nationality, $address, $notes]) {
    $stmt->execute([
        'full_name' => $name,
        'email' => $email,
        'phone' => $phone,
        'id_type' => $idType,
        'id_number' => $idNumber,
        'nationality' => $nationality,
        'address' => $address,
        'notes' => $notes,
    ]);
}

echo 'Seeded ' . count($demo) . " guests.\n";
