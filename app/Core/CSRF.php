<?php

declare(strict_types=1);

namespace App\Core;

final class CSRF
{
    private const SESSION_KEY = '_csrf_token';

    public static function token(): string
    {
        $token = Session::get(self::SESSION_KEY);
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            Session::put(self::SESSION_KEY, $token);
        }

        return $token;
    }

    public static function field(): string
    {
        $token = e(self::token());

        return '<input type="hidden" name="_csrf" value="' . $token . '">';
    }

    public static function validate(?string $token): bool
    {
        $sessionToken = Session::get(self::SESSION_KEY);
        if (!is_string($sessionToken) || $sessionToken === '' || $token === null || $token === '') {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }

    public static function validateRequest(Request $request): void
    {
        $token = $request->input('_csrf');
        if (!is_string($token)) {
            $token = $request->header('X-CSRF-TOKEN');
        }

        if (!self::validate(is_string($token) ? $token : null)) {
            Response::html(
                '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>Invalid CSRF</title></head>'
                . '<body><h1>419</h1><p>Invalid or missing CSRF token. Go back and try again.</p></body></html>',
                419
            );
            exit;
        }
    }
}
