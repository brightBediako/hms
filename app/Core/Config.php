<?php

declare(strict_types=1);

namespace App\Core;

final class Config
{
    /** @var array<string, array<string, mixed>> */
    private static array $cache = [];

    /** @return array<string, mixed> */
    public static function get(string $name): array
    {
        if (isset(self::$cache[$name])) {
            return self::$cache[$name];
        }

        $path = HMS_ROOT . '/config/' . $name . '.php';
        if (!is_file($path)) {
            throw new \RuntimeException('Config file not found: ' . $name);
        }

        /** @var array<string, mixed> $config */
        $config = require $path;
        self::$cache[$name] = $config;

        return $config;
    }

    public static function app(string $key, mixed $default = null): mixed
    {
        $config = self::get('app');

        return $config[$key] ?? $default;
    }

    public static function database(string $key, mixed $default = null): mixed
    {
        $config = self::get('database');

        return $config[$key] ?? $default;
    }
}
