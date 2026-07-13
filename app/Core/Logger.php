<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

/**
 * Simple file logger under storage/logs (never exposed via HTTP when docroot is public/).
 */
final class Logger
{
    public static function error(string $message, ?Throwable $e = null): void
    {
        self::write('ERROR', $message, $e);
    }

    public static function warning(string $message): void
    {
        self::write('WARNING', $message, null);
    }

    public static function info(string $message): void
    {
        self::write('INFO', $message, null);
    }

    private static function write(string $level, string $message, ?Throwable $e): void
    {
        $line = sprintf(
            "[%s] %s %s",
            gmdate('c'),
            $level,
            $message
        );

        if ($e !== null) {
            $line .= sprintf(
                " | %s in %s:%d\n%s",
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                $e->getTraceAsString()
            );
        }

        $line .= PHP_EOL;

        $dir = (defined('HMS_ROOT') ? HMS_ROOT : dirname(__DIR__, 2)) . '/storage/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $file = $dir . '/app-' . gmdate('Y-m-d') . '.log';
        @error_log(rtrim($line));
        @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }
}
