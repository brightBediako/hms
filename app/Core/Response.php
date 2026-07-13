<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function html(string $content, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=UTF-8');
        echo $content;
    }

    /** @param array<string, mixed> $data */
    public static function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function redirect(string $url, int $status = 302): void
    {
        http_response_code($status);
        header('Location: ' . $url);
    }

    public static function text(string $content, int $status = 200): void
    {
        http_response_code($status);
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
