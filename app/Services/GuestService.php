<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Guest;
use App\Models\GuestDocument;

/**
 * Guest profile helpers and validated document storage under storage/uploads.
 */
final class GuestService
{
    private const MAX_UPLOAD_BYTES = 5_242_880; // 5 MB

    /** @var list<string> */
    private const ALLOWED_MIME = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    /** @var array<string, string> */
    private const EXT_BY_MIME = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public function __construct(
        private readonly Guest $guests = new Guest(),
        private readonly GuestDocument $documents = new GuestDocument(),
    ) {
    }

    public function labelForIdType(?string $idType): string
    {
        return match ($idType) {
            'passport' => 'Passport',
            'national_id' => 'National ID',
            'drivers_license' => "Driver's license",
            'other' => 'Other',
            default => '—',
        };
    }

    public function labelForReservationStatus(string $status): string
    {
        return match ($status) {
            'booked' => 'Booked',
            'checked_in' => 'Checked in',
            'checked_out' => 'Checked out',
            'cancelled' => 'Cancelled',
            'no_show' => 'No show',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    /**
     * @param array<string, mixed> $file $_FILES entry
     * @return array{ok: true, id: int}|array{ok: false, error: string}
     */
    public function storeDocument(int $guestId, array $file, ?string $documentType = 'id_scan'): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['ok' => false, 'error' => 'Choose a file to upload.'];
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => 'Upload failed. Try again.'];
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > self::MAX_UPLOAD_BYTES) {
            return ['ok' => false, 'error' => 'File must be under 5 MB.'];
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return ['ok' => false, 'error' => 'Invalid upload.'];
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmp) ?: '';
        if (!in_array($mime, self::ALLOWED_MIME, true)) {
            return ['ok' => false, 'error' => 'Allowed types: PDF, JPG, PNG, WEBP.'];
        }

        $ext = self::EXT_BY_MIME[$mime];
        $relativeDir = 'guests/' . $guestId;
        $absoluteDir = HMS_ROOT . '/storage/uploads/' . $relativeDir;
        if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0755, true) && !is_dir($absoluteDir)) {
            return ['ok' => false, 'error' => 'Could not create upload directory.'];
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        $relativePath = $relativeDir . '/' . $filename;
        $absolutePath = HMS_ROOT . '/storage/uploads/' . $relativePath;

        if (!move_uploaded_file($tmp, $absolutePath)) {
            return ['ok' => false, 'error' => 'Could not save the file.'];
        }

        $type = $documentType !== null && trim($documentType) !== ''
            ? substr(trim($documentType), 0, 50)
            : 'id_scan';

        $id = $this->documents->create($guestId, $relativePath, $type);

        return ['ok' => true, 'id' => $id];
    }

    /**
     * @return array{ok: true}|array{ok: false, error: string}
     */
    public function deleteDocument(int $guestId, int $documentId): array
    {
        $doc = $this->documents->findById($documentId);
        if ($doc === null || (int) $doc['guest_id'] !== $guestId) {
            return ['ok' => false, 'error' => 'Document not found.'];
        }

        $absolute = $this->absolutePath((string) $doc['file_path']);
        $this->documents->delete($documentId);

        if ($absolute !== null && is_file($absolute)) {
            @unlink($absolute);
        }

        return ['ok' => true];
    }

    public function absolutePath(string $relativePath): ?string
    {
        $relativePath = str_replace('\\', '/', $relativePath);
        if ($relativePath === '' || str_contains($relativePath, '..')) {
            return null;
        }

        $base = realpath(HMS_ROOT . '/storage/uploads');
        if ($base === false) {
            return null;
        }

        $full = HMS_ROOT . '/storage/uploads/' . ltrim($relativePath, '/');
        $real = realpath($full);
        if ($real === false || !str_starts_with($real, $base)) {
            // File may not exist yet for realpath; still constrain by prefix
            $normalized = str_replace('\\', '/', $full);
            $baseNorm = str_replace('\\', '/', $base);
            if (!str_starts_with($normalized, $baseNorm . '/') && $normalized !== $baseNorm) {
                return null;
            }

            return is_file($full) ? $full : null;
        }

        return $real;
    }

    public function downloadFilename(array $document): string
    {
        $type = (string) ($document['document_type'] ?? 'document');
        $ext = pathinfo((string) $document['file_path'], PATHINFO_EXTENSION);

        return preg_replace('/[^a-zA-Z0-9_-]/', '_', $type) . ($ext !== '' ? '.' . $ext : '');
    }
}
