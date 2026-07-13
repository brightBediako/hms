<?php

declare(strict_types=1);

/**
 * One-time local helper: set demo admin password to Admin@123
 * Usage: php scripts/set_demo_admin.php
 */

define('HMS_ROOT', dirname(__DIR__));

require HMS_ROOT . '/vendor/autoload.php';

use App\Core\Database;
use App\Core\Env;

Env::load(HMS_ROOT);

$email = 'admin@example.com';
$password = 'Admin@123';
$hash = password_hash($password, PASSWORD_DEFAULT);

$pdo = Database::connection();
$stmt = $pdo->prepare(
    'UPDATE staff SET password_hash = :hash, status = :status WHERE email = :email'
);
$stmt->execute([
    'hash' => $hash,
    'status' => 'active',
    'email' => $email,
]);

if ($stmt->rowCount() === 0) {
    // Insert if missing
    $role = $pdo->query("SELECT id FROM roles WHERE name = 'System Administrator' LIMIT 1")->fetchColumn();
    if (!$role) {
        fwrite(STDERR, "System Administrator role not found. Import schema/seed first.\n");
        exit(1);
    }

    $insert = $pdo->prepare(
        'INSERT INTO staff (role_id, full_name, email, password_hash, status)
         VALUES (:role_id, :full_name, :email, :hash, :status)'
    );
    $insert->execute([
        'role_id' => $role,
        'full_name' => 'System Administrator',
        'email' => $email,
        'hash' => $hash,
        'status' => 'active',
    ]);
    echo "Created demo admin {$email}\n";
} else {
    echo "Updated demo admin {$email}\n";
}

echo "Password set. Sign in at /login\n";
