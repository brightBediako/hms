<?php

declare(strict_types=1);

define('HMS_ROOT', dirname(__DIR__));
require HMS_ROOT . '/vendor/autoload.php';

use App\Core\Env;
use App\Services\BackupService;

Env::load(HMS_ROOT);

$svc = new BackupService();
$result = $svc->createManual(null);
if (!$result['ok']) {
    fwrite(STDERR, $result['error'] . PHP_EOL);
    exit(1);
}

echo "file={$result['filename']} size={$result['size']}\n";
$files = $svc->listFiles();
echo 'listed=' . count($files) . PHP_EOL;
$path = $svc->absolutePath($result['filename']);
echo 'exists=' . ($path !== null ? 'yes' : 'no') . PHP_EOL;
echo "smoke ok\n";
