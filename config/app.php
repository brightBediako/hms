<?php

declare(strict_types=1);

use App\Core\Env;

return [
    'name' => (string) Env::get('APP_NAME', 'Hotel Management System'),
    'env' => (string) Env::get('APP_ENV', 'local'),
    'debug' => Env::bool('APP_DEBUG', false),
    'url' => (string) Env::get('APP_URL', 'http://localhost/hms/public'),
    'key' => (string) Env::get('APP_KEY', ''),
    'timezone' => (string) Env::get('TIMEZONE', 'Africa/Accra'),
    'currency' => (string) Env::get('CURRENCY', 'GHS'),
    'tax_rate' => (float) Env::get('TAX_RATE', '0.125'),
    'session_lifetime' => Env::int('SESSION_LIFETIME', 120),
    'session_secure' => Env::bool('SESSION_SECURE', false),
    'trust_proxy' => Env::bool('TRUST_PROXY', false),
];
