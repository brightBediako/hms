<?php

declare(strict_types=1);

/**
 * Seed a demo receptionist account (idempotent by email).
 * Usage: php scripts/seed_staff.php
 */

define('HMS_ROOT', dirname(__DIR__));
require HMS_ROOT . '/vendor/autoload.php';

use App\Core\Database;
use App\Core\Env;
use App\Services\StaffService;

Env::load(HMS_ROOT);

$pdo = Database::connection();
$role = $pdo->query("SELECT id FROM roles WHERE name = 'Receptionist' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if ($role === false) {
    fwrite(STDERR, "Receptionist role missing. Run seed SQL first.\n");
    exit(1);
}

$email = 'receptionist@example.com';
$existing = $pdo->prepare('SELECT id FROM staff WHERE email = :email LIMIT 1');
$existing->execute(['email' => $email]);
if ($existing->fetchColumn()) {
    echo "already exists: {$email}\n";
    exit(0);
}

$svc = new StaffService();
$result = $svc->create([
    'full_name' => 'Demo Receptionist',
    'email' => $email,
    'phone' => '+233200000001',
    'role_id' => (int) $role['id'],
    'status' => 'active',
    'password' => 'Reception@123',
]);

if (!$result['ok']) {
    fwrite(STDERR, $result['error'] . PHP_EOL);
    exit(1);
}

echo "staff=#{$result['id']} {$email} / Reception@123\n";
echo "seed ok\n";
