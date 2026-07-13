<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Expense;
use App\Models\ExpenseCategory;

final class ExpenseService
{
    private const MAX_UPLOAD_BYTES = 5_242_880;

    private const ALLOWED_MIME = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    private const EXT_BY_MIME = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public function __construct(
        private readonly Expense $expenses = new Expense(),
        private readonly ExpenseCategory $categories = new ExpenseCategory(),
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function categories(): array
    {
        return $this->categories->all();
    }

    /**
     * @param array{
     *   category_id: int,
     *   description: string,
     *   amount: float|string,
     *   expense_date: string,
     *   receipt?: array<string, mixed>|null
     * } $data
     * @return array{ok: true, id: int}|array{ok: false, error: string}
     */
    public function record(array $data, ?int $staffId): array
    {
        $categoryId = (int) $data['category_id'];
        if ($this->categories->findById($categoryId) === null) {
            return ['ok' => false, 'error' => 'Select a valid expense category.'];
        }

        $description = trim((string) $data['description']);
        if ($description === '') {
            return ['ok' => false, 'error' => 'Description is required.'];
        }

        $amount = round((float) $data['amount'], 2);
        if ($amount <= 0) {
            return ['ok' => false, 'error' => 'Amount must be greater than zero.'];
        }

        $expenseDate = trim((string) $data['expense_date']);
        if ($expenseDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $expenseDate)) {
            return ['ok' => false, 'error' => 'Enter a valid expense date.'];
        }

        $receiptPath = null;
        $file = $data['receipt'] ?? null;
        if (is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $upload = $this->storeReceipt($file);
            if (!$upload['ok']) {
                return $upload;
            }
            $receiptPath = $upload['path'];
        }

        $id = $this->expenses->create([
            'category_id' => $categoryId,
            'description' => substr($description, 0, 255),
            'amount' => number_format($amount, 2, '.', ''),
            'expense_date' => $expenseDate,
            'recorded_by' => $staffId,
            'receipt_path' => $receiptPath,
        ]);

        return ['ok' => true, 'id' => $id];
    }

    /**
     * @return array{ok: true}|array{ok: false, error: string}
     */
    public function delete(int $id): array
    {
        $row = $this->expenses->findById($id);
        if ($row === null) {
            return ['ok' => false, 'error' => 'Expense not found.'];
        }

        $this->expenses->delete($id);

        if (!empty($row['receipt_path'])) {
            $absolute = $this->absolutePath((string) $row['receipt_path']);
            if ($absolute !== null && is_file($absolute)) {
                @unlink($absolute);
            }
        }

        return ['ok' => true];
    }

    /**
     * @param array{name: string} $data
     * @return array{ok: true, id: int}|array{ok: false, error: string}
     */
    public function createCategory(array $data): array
    {
        $name = trim((string) $data['name']);
        if ($name === '') {
            return ['ok' => false, 'error' => 'Category name is required.'];
        }

        $name = substr($name, 0, 80);

        foreach ($this->categories->all() as $existing) {
            if (strcasecmp((string) $existing['name'], $name) === 0) {
                return ['ok' => false, 'error' => 'That category already exists.'];
            }
        }

        try {
            $id = $this->categories->create($name);
        } catch (\PDOException) {
            return ['ok' => false, 'error' => 'Could not create category (duplicate name?).'];
        }

        return ['ok' => true, 'id' => $id];
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
            $normalized = str_replace('\\', '/', $full);
            $baseNorm = str_replace('\\', '/', $base);
            if (!str_starts_with($normalized, $baseNorm . '/') && $normalized !== $baseNorm) {
                return null;
            }

            return is_file($full) ? $full : null;
        }

        return $real;
    }

    /**
     * @param array<string, mixed> $file
     * @return array{ok: true, path: string}|array{ok: false, error: string}
     */
    private function storeReceipt(array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => 'Receipt upload failed. Try again.'];
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > self::MAX_UPLOAD_BYTES) {
            return ['ok' => false, 'error' => 'Receipt must be under 5 MB.'];
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return ['ok' => false, 'error' => 'Invalid receipt upload.'];
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmp) ?: '';
        if (!in_array($mime, self::ALLOWED_MIME, true)) {
            return ['ok' => false, 'error' => 'Receipt types allowed: PDF, JPG, PNG, WEBP.'];
        }

        $ext = self::EXT_BY_MIME[$mime];
        $relativeDir = 'expenses/' . date('Y/m');
        $absoluteDir = HMS_ROOT . '/storage/uploads/' . $relativeDir;
        if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0755, true) && !is_dir($absoluteDir)) {
            return ['ok' => false, 'error' => 'Could not create receipt directory.'];
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        $relativePath = $relativeDir . '/' . $filename;
        $absolutePath = HMS_ROOT . '/storage/uploads/' . $relativePath;

        if (!move_uploaded_file($tmp, $absolutePath)) {
            return ['ok' => false, 'error' => 'Could not save the receipt.'];
        }

        return ['ok' => true, 'path' => $relativePath];
    }
}
