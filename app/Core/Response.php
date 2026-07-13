<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function sendSecurityHeaders(): void
    {
        if (headers_sent()) {
            return;
        }

        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        header('X-XSS-Protection: 0');

        $url = (string) Config::app('url', '');
        if (str_starts_with(strtolower($url), 'https://')) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }

    public static function html(string $content, int $status = 200): void
    {
        http_response_code($status);
        self::sendSecurityHeaders();
        header('Content-Type: text/html; charset=UTF-8');
        echo $content;
    }

    /** @param array<string, mixed> $data */
    public static function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        self::sendSecurityHeaders();
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function redirect(string $url, int $status = 302): void
    {
        // 303 after POST so clients switch to GET on the next URL
        if ($status === 302 && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $status = 303;
        }

        http_response_code($status);
        self::sendSecurityHeaders();
        header('Location: ' . $url);
        exit;
    }

    public static function text(string $content, int $status = 200): void
    {
        http_response_code($status);
        self::sendSecurityHeaders();
        header('Content-Type: text/plain; charset=UTF-8');
        echo $content;
    }

    public static function notFound(string $message = 'Not Found'): void
    {
        self::html(
            '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
            . '<title>404</title></head><body><h1>404</h1><p>'
            . htmlspecialchars($message, ENT_QUOTES, 'UTF-8')
            . '</p></body></html>',
            404
        );
    }
}
