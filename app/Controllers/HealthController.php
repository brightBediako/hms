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
        $dbOk = false;

        try {
            Database::connection()->query('SELECT 1');
            $dbOk = true;
        } catch (Throwable) {
            $dbOk = false;
        }

        $overall = $dbOk ? 'ok' : 'error';
        $payload = [
            'status' => $overall,
            'time' => gmdate('c'),
        ];

        // Extra detail only in local/debug — never expose DB name/env in production.
        $env = (string) Config::app('env', 'production');
        $debug = (bool) Config::app('debug', false);
        if ($debug || $env === 'local') {
            $payload['app'] = Config::app('name', 'HMS');
            $payload['env'] = $env;
            $payload['database'] = $dbOk ? 'ok' : 'error';
        }

        Response::json($payload, $dbOk ? 200 : 503);
    }
}
