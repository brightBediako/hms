<?php

declare(strict_types=1);

/**
 * Display helpers for currency and dates.
 */

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('format_money')) {
    function format_money(int|float|string|null $amount, ?string $currency = null): string
    {
        $currency ??= (string) (\App\Core\Config::app('currency') ?? 'GHS');
        $value = is_numeric($amount) ? (float) $amount : 0.0;

        return $currency . ' ' . number_format($value, 2, '.', ',');
    }
}

if (!function_exists('format_date')) {
    function format_date(?string $datetime, string $format = 'd M Y'): string
    {
        if ($datetime === null || $datetime === '') {
            return '';
        }

        $timestamp = strtotime($datetime);
        if ($timestamp === false) {
            return $datetime;
        }

        return date($format, $timestamp);
    }
}

if (!function_exists('format_datetime')) {
    function format_datetime(?string $datetime, string $format = 'd M Y H:i'): string
    {
        return format_date($datetime, $format);
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
        $base = rtrim(dirname($scriptName), '/');
        if ($base === '/' || $base === '\\') {
            $base = '';
        }

        $path = '/' . ltrim($path, '/');
        if ($path === '/') {
            return $base === '' ? '/' : $base . '/';
        }

        return $base . $path;
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path): never
    {
        \App\Core\Response::redirect(url($path));
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return url('/assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('hotel_name')) {
    function hotel_name(): string
    {
        static $cached = null;
        if (is_string($cached)) {
            return $cached;
        }

        try {
            $stmt = \App\Core\Database::connection()->prepare(
                'SELECT `value` FROM settings WHERE `key` = :key LIMIT 1'
            );
            $stmt->execute(['key' => 'hotel_name']);
            $value = $stmt->fetchColumn();
            if (is_string($value) && $value !== '') {
                $cached = $value;
                return $cached;
            }
        } catch (Throwable) {
            // Fall through to app name
        }

        $cached = (string) \App\Core\Config::app('name', 'Hotel Management System');

        return $cached;
    }
}

if (!function_exists('current_path')) {
    function current_path(): string
    {
        static $path = null;
        if (is_string($path)) {
            return $path;
        }

        $path = \App\Core\Request::capture()->path();

        return $path;
    }
}

if (!function_exists('nav_is_active')) {
    function nav_is_active(string $path): bool
    {
        $current = current_path();
        if ($path === '/dashboard') {
            return $current === '/dashboard' || $current === '/';
        }

        if ($current === $path) {
            return true;
        }

        // Prefix match, but avoid /rooms matching /rooms/types
        $prefix = rtrim($path, '/') . '/';
        if (!str_starts_with($current, $prefix)) {
            return false;
        }

        if ($path === '/rooms' && str_starts_with($current, '/rooms/types')) {
            return false;
        }

        return true;
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = ''): mixed
    {
        $old = \App\Core\Session::get('_flash_old');
        if (is_array($old) && array_key_exists($key, $old)) {
            return $old[$key];
        }

        return $default;
    }
}
