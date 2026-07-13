<?php

declare(strict_types=1);

use App\Core\Env;

return [
    'name' => (string) Env::get('APP_NAME', 'Hotel Management System'),
    'env' => (string) Env::get('APP_ENV', 'local'),
    'debug' => Env::bool('APP_DEBUG', true),
    'url' => (string) Env::get('APP_URL', 'http://localhost/hms/public'),
    'key' => (string) Env::get('APP_KEY', ''),
    'timezone' => (string) Env::get('TIMEZONE', 'Africa/Accra'),
    'currency' => (string) Env::get('CURRENCY', 'GHS'),
    'session_lifetime' => Env::int('SESSION_LIFETIME', 120),
];
