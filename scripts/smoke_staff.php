<?php

declare(strict_types=1);

define('HMS_ROOT', dirname(__DIR__));
require HMS_ROOT . '/vendor/autoload.php';

use App\Core\Env;
use App\Models\Role;
use App\Models\Staff;
use App\Services\StaffService;

Env::load(HMS_ROOT);

$staff = new Staff();
$roles = new Role();
$svc = new StaffService();

echo 'staff=' . count($staff->filtered([])) . ' roles=' . count($roles->all()) . PHP_EOL;

$member = $staff->findByEmail('receptionist@example.com');
if ($member === null) {
    fwrite(STDERR, "receptionist missing\n");
    exit(1);
}

$result = $svc->update((int) $member['id'], [
    'full_name' => 'Demo Receptionist',
    'email' => 'receptionist@example.com',
    'phone' => '+233200000001',
    'role_id' => (int) $member['role_id'],
    'status' => 'active',
    'password' => null,
], null);

echo ($result['ok'] ? 'update ok' : ('fail: ' . $result['error'])) . PHP_EOL;
echo 'perms=' . count($roles->permissions((int) $member['role_id'])) . PHP_EOL;
echo "smoke ok\n";
