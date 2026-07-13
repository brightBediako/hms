<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Config;
use App\Core\Env;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Session;

define('HMS_ROOT', dirname(__DIR__));

$autoload = HMS_ROOT . '/vendor/autoload.php';
if (is_file($autoload)) {
    require $autoload;
} else {
    require HMS_ROOT . '/app/bootstrap.php';
}

Env::load(HMS_ROOT);

date_default_timezone_set((string) Config::app('timezone', 'Africa/Accra'));

Session::start();
Auth::enforceIdleTimeout();

$router = new Router();
require HMS_ROOT . '/config/routes.php';

try {
    $request = Request::capture();
    $router->dispatch($request);
} catch (Throwable $e) {
    $debug = Config::app('debug', true);

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
