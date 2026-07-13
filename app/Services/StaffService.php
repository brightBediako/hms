<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Role;
use App\Models\Staff;

final class StaffService
{
    public function __construct(
        private readonly Staff $staff = new Staff(),
        private readonly Role $roles = new Role(),
    ) {
    }

    public function labelForStatus(string $status): string
    {
        return match ($status) {
            'active' => 'Active',
            'suspended' => 'Suspended',
            default => ucfirst($status),
        };
    }

    /**
     * @return array{bg: string, text: string}
     */
    public function statusChipClasses(string $status): array
    {
        return match ($status) {
            'active' => ['bg' => 'bg-primary-fixed', 'text' => 'text-on-primary-fixed-variant'],
            'suspended' => ['bg' => 'bg-error-container', 'text' => 'text-on-error-container'],
            default => ['bg' => 'bg-surface-container-high', 'text' => 'text-on-surface-variant'],
        };
    }

    /**
     * @param array{
     *   full_name: string,
     *   email: string,
     *   phone?: ?string,
     *   role_id: int,
     *   status: string,
     *   password: string
     * } $data
     * @return array{ok: true, id: int}|array{ok: false, error: string}
     */
    public function create(array $data): array
    {
        $normalized = $this->normalizeProfile($data, null);
        if (!$normalized['ok']) {
            return $normalized;
        }

        $password = (string) $data['password'];
        if (strlen($password) < 8) {
            return ['ok' => false, 'error' => 'Password must be at least 8 characters.'];
        }

        $id = $this->staff->create([
            ...$normalized['data'],
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ]);

        $audit = new AuditService();
        $audit->log(
            'staff.create',
            'staff',
            $id,
            null,
            $audit->snapshot($normalized['data'] + ['id' => $id], ['full_name', 'email', 'phone', 'role_id', 'status']),
            null,
        );

        return ['ok' => true, 'id' => $id];
    }

    /**
     * @param array{
     *   full_name: string,
     *   email: string,
     *   phone?: ?string,
     *   role_id: int,
     *   status: string,
     *   password?: ?string
     * } $data
     * @return array{ok: true}|array{ok: false, error: string}
     */
    public function update(int $id, array $data, ?int $actorId): array
    {
        $existing = $this->staff->findById($id);
        if ($existing === null) {
            return ['ok' => false, 'error' => 'Staff member not found.'];
        }

        $normalized = $this->normalizeProfile($data, $id);
        if (!$normalized['ok']) {
            return $normalized;
        }

        if ($actorId !== null && $actorId === $id && $normalized['data']['status'] === 'suspended') {
            return ['ok' => false, 'error' => 'You cannot suspend your own account.'];
        }

        $this->staff->update($id, $normalized['data']);

        $password = isset($data['password']) ? trim((string) $data['password']) : '';
        $passwordChanged = false;
        if ($password !== '') {
            if (strlen($password) < 8) {
                return ['ok' => false, 'error' => 'New password must be at least 8 characters.'];
            }
            $this->staff->updatePassword($id, password_hash($password, PASSWORD_DEFAULT));
            $passwordChanged = true;
        }

        $audit = new AuditService();
        $newSnap = $audit->snapshot($normalized['data'], ['full_name', 'email', 'phone', 'role_id', 'status']);
        if ($passwordChanged && is_array($newSnap)) {
            $newSnap['password'] = '[changed]';
        }
        $audit->log(
            'staff.update',
            'staff',
            $id,
            $audit->snapshot($existing, ['full_name', 'email', 'phone', 'role_id', 'status']),
            $newSnap,
            $actorId,
        );

        return ['ok' => true];
    }

    /**
     * @param array<string, mixed> $data
     * @return array{ok: true, data: array<string, mixed>}|array{ok: false, error: string}
     */
    private function normalizeProfile(array $data, ?int $exceptId): array
    {
        $fullName = trim((string) $data['full_name']);
        if ($fullName === '') {
            return ['ok' => false, 'error' => 'Full name is required.'];
        }

        $email = strtolower(trim((string) $data['email']));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => 'Enter a valid email address.'];
        }

        if ($this->staff->emailExists($email, $exceptId)) {
            return ['ok' => false, 'error' => 'That email is already in use.'];
        }

        $roleId = (int) $data['role_id'];
        if ($this->roles->findById($roleId) === null) {
            return ['ok' => false, 'error' => 'Select a valid role.'];
        }

        $status = (string) $data['status'];
        if (!in_array($status, Staff::STATUSES, true)) {
            return ['ok' => false, 'error' => 'Select a valid status.'];
        }

        $phone = isset($data['phone']) ? trim((string) $data['phone']) : '';

        return [
            'ok' => true,
            'data' => [
                'full_name' => substr($fullName, 0, 100),
                'email' => substr($email, 0, 150),
                'phone' => $phone === '' ? null : substr($phone, 0, 30),
                'role_id' => $roleId,
                'status' => $status,
            ],
        ];
    }
}
