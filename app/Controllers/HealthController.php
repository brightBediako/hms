<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Config;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use Throwable;

final class HealthController
{
    public function ping(Request $request): void
    {
        $database = [
            'status' => 'disconnected',
            'database' => Config::database('database'),
        ];

        try {
            $pdo = Database::connection();
            $stmt = $pdo->prepare('SELECT COUNT(*) AS role_count FROM roles');
            $stmt->execute();
            $row = $stmt->fetch();

            $database = [
                'status' => 'ok',
                'database' => Config::database('database'),
                'roles' => (int) ($row['role_count'] ?? 0),
            ];
        } catch (Throwable $e) {
            $database['status'] = 'error';
            if (Config::app('debug', false)) {
                $database['error'] = $e->getMessage();
            }
        }

        $overall = $database['status'] === 'ok' ? 'ok' : 'degraded';

        Response::json([
            'status' => $overall,
            'app' => Config::app('name', 'HMS'),
            'env' => Config::app('env', 'local'),
            'currency' => Config::app('currency', 'GHS'),
            'path' => $request->path(),
            'method' => $request->method(),
            'time' => gmdate('c'),
            'database' => $database,
        ], $overall === 'ok' ? 200 : 503);
    }

    public function home(Request $request): void
    {
        Response::html(
            '<!DOCTYPE html>'
            . '<html lang="en">'
            . '<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>HMS</title>'
            . '<style>'
            . 'body{font-family:system-ui,sans-serif;background:#f8faf7;color:#191c1b;margin:0;padding:2rem;}'
            . 'main{max-width:40rem;margin:0 auto;background:#fff;border:1px solid #bfc9c4;border-radius:8px;padding:1.5rem;}'
            . 'h1{font-size:1.5rem;margin:0 0 .5rem;color:#00342b;}'
            . 'p{margin:.5rem 0;line-height:1.5;}'
            . 'a{color:#004d40;}'
            . 'code{font-family:ui-monospace,monospace;font-size:.875rem;}'
            . '</style></head><body><main>'
            . '<h1>' . e((string) Config::app('name', 'Hotel Management System')) . '</h1>'
            . '<p>Front controller, routing, and database plumbing are in place.</p>'
            . '<p>Request path: <code>' . e($request->path()) . '</code></p>'
            . '<p>Health check: <a href="health"><code>/health</code></a></p>'
            . '</main></body></html>'
        );
    }
}
