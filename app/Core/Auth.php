<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Staff;

final class Auth
{
    private const STAFF_ID_KEY = 'staff_id';
    private const PERMISSIONS_KEY = 'permissions';

    public static function attempt(string $email, string $password): bool
    {
        $staffModel = new Staff();
        $staff = $staffModel->findByEmail($email);

        if ($staff === null) {
            return false;
        }

        if (($staff['status'] ?? '') !== 'active') {
            return false;
        }

        if (!password_verify($password, (string) $staff['password_hash'])) {
            return false;
        }

        Session::regenerate(true);

        $permissions = $staffModel->permissionKeysForRole((int) $staff['role_id']);
        Session::put(self::STAFF_ID_KEY, (int) $staff['id']);
        Session::put(self::PERMISSIONS_KEY, $permissions);
        Session::touchActivity();

        $staffModel->touchLastLogin((int) $staff['id']);

        return true;
    }

    public static function logout(): void
    {
        Session::forget(self::STAFF_ID_KEY);
        Session::forget(self::PERMISSIONS_KEY);
        Session::regenerate(true);
    }

    public static function check(): bool
    {
        return self::id() !== null;
    }

    public static function id(): ?int
    {
        $id = Session::get(self::STAFF_ID_KEY);
        return is_int($id) ? $id : (is_numeric($id) ? (int) $id : null);
    }

    /** @return array<string, mixed>|null */
    public static function user(): ?array
    {
        $id = self::id();
        if ($id === null) {
            return null;
        }

        return (new Staff())->findById($id);
    }

    public static function can(string $permission): bool
    {
        if (!self::check()) {
            return false;
        }

        $permissions = Session::get(self::PERMISSIONS_KEY, []);
        if (!is_array($permissions)) {
            return false;
        }

        return in_array($permission, $permissions, true);
    }

    /** @return list<string> */
    public static function permissions(): array
    {
        $permissions = Session::get(self::PERMISSIONS_KEY, []);
        if (!is_array($permissions)) {
            return [];
        }

        /** @var list<string> $list */
        $list = array_values(array_filter($permissions, 'is_string'));

        return $list;
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            Session::flash('error', 'Please sign in to continue.');
            Response::redirect(url('/login'));
            exit;
        }

        self::enforceIdleTimeout();
    }

    public static function requirePermission(string $permission): void
    {
        self::requireLogin();

        if (!self::can($permission)) {
            Response::html(
                '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>Forbidden</title></head>'
                . '<body><h1>403</h1><p>You do not have permission to perform this action.</p>'
                . '<p><a href="' . e(url('/')) . '">Back</a></p></body></html>',
                403
            );
            exit;
        }
    }

    public static function requireGuest(): void
    {
        if (self::check()) {
            Response::redirect(url('/dashboard'));
            exit;
        }
    }

    public static function enforceIdleTimeout(): void
    {
        if (!self::check()) {
            return;
        }

        $lifetimeMinutes = (int) Config::app('session_lifetime', 120);
        $idleSeconds = max(1, $lifetimeMinutes) * 60;
        $last = Session::lastActivity();

        if ((time() - $last) > $idleSeconds) {
            self::logout();
            Session::flash('error', 'Your session expired due to inactivity. Please sign in again.');
            Response::redirect(url('/login'));
            exit;
        }

        Session::touchActivity();
    }
}
