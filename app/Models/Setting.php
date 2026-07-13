<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Setting
{
    /** @return array<string, string> */
    public function all(): array
    {
        $stmt = Database::connection()->query(
            'SELECT `key`, `value` FROM settings ORDER BY `key` ASC'
        );

        $map = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $map[(string) $row['key']] = (string) ($row['value'] ?? '');
        }

        return $map;
    }

    public function get(string $key): ?string
    {
        $stmt = Database::connection()->prepare(
            'SELECT `value` FROM settings WHERE `key` = :key LIMIT 1'
        );
        $stmt->execute(['key' => $key]);
        $value = $stmt->fetchColumn();

        return $value === false ? null : (string) $value;
    }

    public function set(string $key, ?string $value): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO settings (`key`, `value`) VALUES (:key, :value)
             ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)'
        );
        $stmt->execute([
            'key' => $key,
            'value' => $value,
        ]);
    }
}
