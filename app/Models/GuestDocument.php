<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class GuestDocument
{
    /**
     * @return list<array<string, mixed>>
     */
    public function forGuest(int $guestId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM guest_documents
             WHERE guest_id = :guest_id
             ORDER BY uploaded_at DESC'
        );
        $stmt->execute(['guest_id' => $guestId]);

        /** @var list<array<string, mixed>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rows;
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM guest_documents WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function create(int $guestId, string $filePath, ?string $documentType): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO guest_documents (guest_id, file_path, document_type)
             VALUES (:guest_id, :file_path, :document_type)'
        );
        $stmt->execute([
            'guest_id' => $guestId,
            'file_path' => $filePath,
            'document_type' => $documentType,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public function delete(int $id): void
    {
        $stmt = Database::connection()->prepare(
            'DELETE FROM guest_documents WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
    }
}
