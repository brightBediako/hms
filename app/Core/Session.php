<?php

declare(strict_types=1);

namespace App\Core;

final class Session
{
    private static bool $started = false;

    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        $lifetimeMinutes = (int) Config::app('session_lifetime', 120);
        $lifetimeSeconds = max(1, $lifetimeMinutes) * 60;
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

        session_name('hms_session');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.gc_maxlifetime', (string) $lifetimeSeconds);

        session_start();
        self::$started = true;

        if (!isset($_SESSION['_created_at'])) {
            $_SESSION['_created_at'] = time();
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    public static function pullFlash(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);

        return $value;
    }

    public static function regenerate(bool $deleteOld = true): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            self::start();
        }

        session_regenerate_id($deleteOld);
        $_SESSION['_created_at'] = time();
        $_SESSION['_last_activity'] = time();
    }

    public static function touchActivity(): void
    {
        $_SESSION['_last_activity'] = time();
    }

    public static function lastActivity(): int
    {
        return (int) ($_SESSION['_last_activity'] ?? $_SESSION['_created_at'] ?? time());
    }

    public static function destroy(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool) $params['secure'], (bool) $params['httponly']);
        }

        session_destroy();
        self::$started = false;
    }
}
