<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class BackupLog
{
    /**
     * @return list<array<string, mixed>>
     */
    public function recent(int $limit = 50): array
    {
        $stmt = Database::connection()->query(
            'SELECT * FROM backup_logs
             ORDER BY created_at DESC, id DESC
             LIMIT ' . max(1, min(200, $limit))
        );

        /** @var list<array<string, mixed>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rows;
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO backup_logs (file_path, file_size_bytes, status, triggered_by)
             VALUES (:file_path, :file_size_bytes, :status, :triggered_by)'
        );
        $stmt->execute([
            'file_path' => $data['file_path'],
            'file_size_bytes' => $data['file_size_bytes'],
            'status' => $data['status'],
            'triggered_by' => $data['triggered_by'],
        ]);

        return (int) Database::connection()->lastInsertId();
    }
}
