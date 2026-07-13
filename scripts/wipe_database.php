<?php

declare(strict_types=1);

/**
 * Delete ALL rows from every HMS table (keeps table structure).
 *
 * DANGEROUS — irreversible. CLI only.
 *
 * Usage:
 *   php scripts/wipe_database.php --confirm
 *   php scripts/wipe_database.php --confirm --keep-auth
 *
 * Options:
 *   --confirm     Required. Without this, the script aborts.
 *   --keep-auth   Keep roles, permissions, role_permissions, and staff
 *                 (useful so you can still log in after wiping hotel data).
 *   --keep-settings  Keep settings rows.
 *
 * After a full wipe, re-seed with:
 *   mysql … < db/hms_seed_data.sql
 *   php scripts/ensure_system_admin.php
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

$args = array_slice($argv, 1);
$confirm = in_array('--confirm', $args, true);
$keepAuth = in_array('--keep-auth', $args, true);
$keepSettings = in_array('--keep-settings', $args, true);

if (!$confirm) {
    fwrite(STDERR, "Refusing to wipe without --confirm\n");
    fwrite(STDERR, "Example: php scripts/wipe_database.php --confirm\n");
    fwrite(STDERR, "Optional: --keep-auth  --keep-settings\n");
    exit(1);
}

/** @var list<string> $tables Child → parent order is irrelevant when FK checks are off. */
$tables = [
    'payments',
    'invoice_items',
    'invoices',
    'reservation_transfers',
    'reservations',
    'guest_documents',
    'guests',
    'housekeeping_tasks',
    'maintenance_requests',
    'expenses',
    'expense_categories',
    'room_status_log',
    'rooms',
    'rate_plans',
    'room_types',
    'notifications',
    'audit_logs',
    'backup_logs',
    'settings',
    'role_permissions',
    'staff',
    'permissions',
    'roles',
];

if ($keepAuth) {
    $tables = array_values(array_filter(
        $tables,
        static fn (string $t): bool => !in_array($t, ['roles', 'permissions', 'role_permissions', 'staff'], true)
    ));
}

if ($keepSettings) {
    $tables = array_values(array_filter(
        $tables,
        static fn (string $t): bool => $t !== 'settings'
    ));
}

$dbName = (string) (\App\Core\Config::database('database') ?? 'hms');
echo "Wiping database: {$dbName}\n";
echo 'Tables: ' . implode(', ', $tables) . "\n";

$pdo = Database::connection();
$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

$wiped = 0;
foreach ($tables as $table) {
    try {
        $count = (int) $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
        $pdo->exec("DELETE FROM `{$table}`");
        try {
            $pdo->exec("ALTER TABLE `{$table}` AUTO_INCREMENT = 1");
        } catch (Throwable) {
            // settings uses string PK — ignore
        }
        echo "  cleared {$table} ({$count} row(s))\n";
        $wiped++;
    } catch (Throwable $e) {
        // Table may not exist on older installs — skip with a note.
        fwrite(STDERR, "  skip {$table}: " . $e->getMessage() . "\n");
    }
}

$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

echo "Done. Wiped {$wiped} table(s).\n";

if (!$keepAuth) {
    echo "Staff/roles were cleared. Re-import db/hms_seed_data.sql (and set admin password) before logging in.\n";
}
