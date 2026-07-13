<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

final class HealthController
{
    public function ping(Request $request): void
    {
        Response::json([
            'status' => 'ok',
            'app' => 'HMS',
            'message' => 'Project shell and routing are online',
            'path' => $request->path(),
            'method' => $request->method(),
            'time' => gmdate('c'),
        ]);
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
            . '<h1>Hotel Management System</h1>'
            . '<p>Front controller and router are running.</p>'
            . '<p>Request path: <code>' . htmlspecialchars($request->path(), ENT_QUOTES, 'UTF-8') . '</code></p>'
            . '<p>Health check: <a href="health"><code>/health</code></a></p>'
            . '</main></body></html>'
        );
    }
}

