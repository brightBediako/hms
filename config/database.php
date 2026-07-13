<?php

declare(strict_types=1);

use App\Core\Env;

return [
    'host' => (string) Env::get('DB_HOST', '127.0.0.1'),
    'port' => Env::int('DB_PORT', 3306),
    'database' => (string) Env::get('DB_DATABASE', 'hms'),
    'username' => (string) Env::get('DB_USERNAME', 'root'),
    'password' => (string) Env::get('DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
];
