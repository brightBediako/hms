<?php

declare(strict_types=1);

use App\Core\Request;
use App\Core\Response;
use App\Core\Router;

define('HMS_ROOT', dirname(__DIR__));

$autoload = HMS_ROOT . '/vendor/autoload.php';
if (is_file($autoload)) {
    require $autoload;
} else {
    require HMS_ROOT . '/app/bootstrap.php';
}

$router = new Router();
require HMS_ROOT . '/config/routes.php';

try {
    $request = Request::capture();
    $router->dispatch($request);
} catch (Throwable $e) {
    $debug = true;
    $envFile = HMS_ROOT . '/.env';
    if (is_file($envFile)) {
        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = array_map('trim', explode('=', $line, 2));
            $value = trim($value, "\"'");
            if ($key === 'APP_DEBUG') {
                $debug = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            }
        }
    }

    if ($debug) {
        Response::html(
            '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>Error</title></head><body>'
            . '<h1>Application error</h1><pre>'
            . htmlspecialchars($e->getMessage() . "\n\n" . $e->getTraceAsString(), ENT_QUOTES, 'UTF-8')
            . '</pre></body></html>',
            500
        );
    } else {
        Response::html(
            '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>Error</title></head><body>'
            . '<h1>Something went wrong</h1></body></html>',
            500
        );
    }
}
