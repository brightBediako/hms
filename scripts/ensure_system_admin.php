<?php

declare(strict_types=1);

/**
 * Ensure the system admin staff account exists from .env:
 *   SYSTEM_ADMIN_NAME
 *   SYSTEM_ADMIN_EMAIL
 *   SYSTEM_ADMIN_PASSWORD
 *
 * Assigns the Owner role (full access). Upserts by email.
 * Usage: php scripts/ensure_system_admin.php
 */

define('HMS_ROOT', dirname(__DIR__));

$autoload = HMS_ROOT . '/vendor/autoload.php';
if (is_file($autoload)) {
    require $autoload;
} else {
    require HMS_ROOT . '/app/bootstrap.php';
}

use App\Core\Database;
use App\Core\Env;

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo 'CLI only.';
    exit(1);
}

Env::load(HMS_ROOT);

$name = trim((string) (Env::get('SYSTEM_ADMIN_NAME') ?? ''));
$email = trim((string) (Env::get('SYSTEM_ADMIN_EMAIL') ?? ''));
$password = (string) (Env::get('SYSTEM_ADMIN_PASSWORD') ?? '');

if ($name === '' || $email === '' || $password === '') {
    fwrite(STDERR, "Set SYSTEM_ADMIN_NAME, SYSTEM_ADMIN_EMAIL, and SYSTEM_ADMIN_PASSWORD in .env\n");
    exit(1);
}

$pdo = Database::connection();

$roleStmt = $pdo->query("SELECT id FROM roles WHERE name = 'Owner' LIMIT 1");
$roleId = (int) $roleStmt->fetchColumn();
if ($roleId < 1) {
    fwrite(STDERR, "Owner role not found. Import db/hms_seed_data.sql first.\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$existing = $pdo->prepare('SELECT id, email FROM staff WHERE email = :email LIMIT 1');
$existing->execute(['email' => $email]);
$row = $existing->fetch(PDO::FETCH_ASSOC);

if ($row) {
    $upd = $pdo->prepare(
        'UPDATE staff
         SET role_id = :role_id,
             full_name = :full_name,
             password_hash = :password_hash,
             status = \'active\'
         WHERE id = :id'
    );
    $upd->execute([
        'id' => (int) $row['id'],
        'role_id' => $roleId,
        'full_name' => $name,
        'password_hash' => $hash,
    ]);
    echo "Updated system admin #{$row['id']} ({$email})\n";
} else {
    $ins = $pdo->prepare(
        'INSERT INTO staff (role_id, full_name, email, phone, password_hash, status)
         VALUES (:role_id, :full_name, :email, NULL, :password_hash, \'active\')'
    );
    $ins->execute([
        'role_id' => $roleId,
        'full_name' => $name,
        'email' => $email,
        'password_hash' => $hash,
    ]);
    $id = (int) $pdo->lastInsertId();
    echo "Created system admin #{$id} ({$email})\n";
}

// Remove legacy demo admin if it is not the configured system admin.
$legacy = $pdo->prepare('DELETE FROM staff WHERE email = :email AND email <> :keep');
$legacy->execute([
    'email' => 'bgh@gmail.com',
    'keep' => $email,
]);
if ($legacy->rowCount() > 0) {
    echo "Removed legacy demo admin bgh@gmail.com\n";
}
