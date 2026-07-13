<?php

declare(strict_types=1);

define('HMS_ROOT', dirname(__DIR__));
require HMS_ROOT . '/vendor/autoload.php';

use App\Core\Env;
use App\Services\BillingService;
use App\Services\SettingsService;

Env::load(HMS_ROOT);

$svc = new SettingsService();
$before = $svc->formDefaults();
echo 'before_name=' . $before['hotel_name'] . ' currency=' . $before['currency'] . PHP_EOL;

$result = $svc->updateHotelSettings([
    'hotel_name' => 'Grand Horizon Test',
    'currency' => 'GHS',
    'tax_rate_percent' => '12.5',
    'check_in_time' => '14:00',
    'check_out_time' => '11:00',
], null);

if (!$result['ok']) {
    fwrite(STDERR, ($result['error'] ?? 'fail') . PHP_EOL);
    exit(1);
}

SettingsService::forgetCache();
$svc2 = new SettingsService();
echo 'hotel_name_helper=' . hotel_name() . PHP_EOL;
echo 'money=' . format_money(100) . PHP_EOL;
echo 'tax=' . (new BillingService())->taxRate() . PHP_EOL;

// Restore previous name if it wasn't the test name
$svc2->updateHotelSettings([
    'hotel_name' => $before['hotel_name'] === 'Grand Horizon Test' ? 'My Hotel' : $before['hotel_name'],
    'currency' => $before['currency'],
    'tax_rate_percent' => $before['tax_rate_percent'],
    'check_in_time' => $before['check_in_time'],
    'check_out_time' => $before['check_out_time'],
], null);

echo "smoke ok\n";
