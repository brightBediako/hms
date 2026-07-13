<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Models\AuditLog;

final class AuditService
{
    /** @var list<string> */
    private const REDACT_KEYS = [
        'password',
        'password_hash',
        'csrf_token',
        '_token',
    ];

    public function __construct(
        private readonly AuditLog $logs = new AuditLog(),
    ) {
    }

    /**
     * Persist an audit entry. Failures are swallowed so business flows stay resilient.
     *
     * @param array<string, mixed>|null $oldValues
     * @param array<string, mixed>|null $newValues
     */
    public function log(
        string $action,
        ?string $tableName = null,
        ?int $recordId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $staffId = null,
    ): void {
        try {
            $actorId = $staffId ?? Auth::id();
            $this->logs->create([
                'staff_id' => $actorId,
                'action' => substr(trim($action), 0, 100),
                'table_name' => $tableName !== null ? substr($tableName, 0, 50) : null,
                'record_id' => $recordId,
                'old_values' => $this->encode($oldValues),
                'new_values' => $this->encode($newValues),
                'ip_address' => $this->clientIp(),
            ]);
        } catch (\Throwable) {
            // Never break the primary write path for audit failures.
        }
    }

    /**
     * Snapshot of common entity fields for before/after diffs (strips secrets).
     *
     * @param array<string, mixed>|null $row
     * @param list<string>|null $only Keys to keep; null keeps a safe default set
     * @return array<string, mixed>|null
     */
    public function snapshot(?array $row, ?array $only = null): ?array
    {
        if ($row === null) {
            return null;
        }

        $clean = $this->redact($row);
        if ($only !== null) {
            $filtered = [];
            foreach ($only as $key) {
                if (array_key_exists($key, $clean)) {
                    $filtered[$key] = $clean[$key];
                }
            }

            return $filtered;
        }

        return $clean;
    }

    public function labelForAction(string $action): string
    {
        return match ($action) {
            'reservation.create' => 'Reservation created',
            'reservation.update' => 'Reservation updated',
            'reservation.cancel' => 'Reservation cancelled',
            'invoice.generate' => 'Invoice generated',
            'invoice.issue' => 'Invoice issued',
            'invoice.void' => 'Invoice voided',
            'invoice.line_add' => 'Invoice line added',
            'invoice.line_remove' => 'Invoice line removed',
            'payment.record' => 'Payment recorded',
            'staff.create' => 'Staff created',
            'staff.update' => 'Staff updated',
            'settings.update' => 'Settings updated',
            'backup.create' => 'Backup created',
            'backup.delete' => 'Backup deleted',
            'backup.failed' => 'Backup failed',
            default => ucfirst(str_replace(['.', '_'], [' · ', ' '], $action)),
        };
    }

    /**
     * @param array<string, mixed>|null $values
     */
    private function encode(?array $values): ?string
    {
        if ($values === null) {
            return null;
        }

        $json = json_encode($this->redact($values), JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return null;
        }

        return $json;
    }

    /**
     * @param array<string, mixed> $values
     * @return array<string, mixed>
     */
    private function redact(array $values): array
    {
        $out = [];
        foreach ($values as $key => $value) {
            $keyStr = (string) $key;
            if (in_array(strtolower($keyStr), self::REDACT_KEYS, true)) {
                $out[$keyStr] = '[redacted]';
                continue;
            }
            if (is_array($value)) {
                /** @var array<string, mixed> $nested */
                $nested = $value;
                $out[$keyStr] = $this->redact($nested);
                continue;
            }
            $out[$keyStr] = $value;
        }

        return $out;
    }

    private function clientIp(): ?string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        if (!is_string($ip) || $ip === '') {
            return null;
        }

        return substr($ip, 0, 45);
    }
}
