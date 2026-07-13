<?php

declare(strict_types=1);

define('HMS_ROOT', dirname(__DIR__));
require HMS_ROOT . '/vendor/autoload.php';

use App\Core\Env;
use App\Services\ReportService;

Env::load(HMS_ROOT);

$svc = new ReportService();
$snap = $svc->dashboardSnapshot();
echo 'occupancy=' . $snap['rooms']['occupancy_pct'] . "% rooms={$snap['rooms']['occupied']}/{$snap['rooms']['total']}\n";
echo "arrivals={$snap['arrivals_count']} departures={$snap['departures_count']} in_house={$snap['in_house_count']}\n";
echo 'revenue_today=' . $snap['revenue_today'] . ' outstanding=' . $snap['outstanding_balance'] . PHP_EOL;

$from = $svc->defaultFrom();
$to = $svc->defaultTo();
$occ = $svc->occupancyReport($from, $to);
$rev = $svc->revenueReport($from, $to);
$exp = $svc->expensesReport($from, $to);
$profit = $svc->profitSummary($from, $to);

echo "range={$from}..{$to}\n";
echo 'occ_nights=' . $occ['room_nights_occupied'] . ' rev=' . $rev['total'] . ' exp=' . $exp['total'] . ' profit=' . $profit['profit'] . PHP_EOL;
echo "smoke ok\n";
