<?php

declare(strict_types=1);

/**
 * Lightweight PSR-4 autoloader for App\ when Composer vendor/ is not installed yet.
 * Prefer vendor/autoload.php after running `composer install`.
 */
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = dirname(__DIR__) . '/app/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($path)) {
        require $path;
    }
});

require_once __DIR__ . '/helpers/format.php';
require_once __DIR__ . '/helpers/permissions.php';
