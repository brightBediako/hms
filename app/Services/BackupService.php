<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Models\BackupLog;

final class BackupService
{
    public const KEEP_COUNT = 10;

    public function __construct(
        private readonly BackupLog $logs = new BackupLog(),
        private readonly AuditService $audit = new AuditService(),
    ) {
    }

    public function backupsDirectory(): string
    {
        return HMS_ROOT . '/storage/backups';
    }

    /**
     * @return list<array{filename: string, path: string, size: int, modified_at: int}>
     */
    public function listFiles(): array
    {
        $dir = $this->backupsDirectory();
        if (!is_dir($dir)) {
            return [];
        }

        $files = [];
        foreach (scandir($dir) ?: [] as $name) {
            if ($name === '.' || $name === '..' || $name === '.gitkeep' || str_starts_with($name, '.')) {
                continue;
            }
            if (!str_ends_with(strtolower($name), '.sql') && !str_ends_with(strtolower($name), '.sql.gz')) {
                continue;
            }
            $full = $dir . '/' . $name;
            if (!is_file($full)) {
                continue;
            }
            $files[] = [
                'filename' => $name,
                'path' => $full,
                'size' => (int) filesize($full),
                'modified_at' => (int) filemtime($full),
            ];
        }

        usort($files, static fn (array $a, array $b): int => $b['modified_at'] <=> $a['modified_at']);

        return $files;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function recentLogs(int $limit = 30): array
    {
        return $this->logs->recent($limit);
    }

    /**
     * @return array{ok: true, filename: string, size: int}|array{ok: false, error: string}
     */
    public function createManual(?int $staffId = null): array
    {
        $dir = $this->backupsDirectory();
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return ['ok' => false, 'error' => 'Could not create backups directory.'];
        }

        $filename = 'hms_' . date('Ymd_His') . '.sql';
        $absolute = $dir . '/' . $filename;

        $dump = $this->runMysqldump($absolute);
        if (!$dump['ok']) {
            $this->logs->create([
                'file_path' => 'storage/backups/' . $filename,
                'file_size_bytes' => null,
                'status' => 'failed',
                'triggered_by' => 'manual',
            ]);
            $this->audit->log('backup.failed', 'backup_logs', null, null, ['error' => $dump['error']], $staffId);

            return $dump;
        }

        $size = is_file($absolute) ? (int) filesize($absolute) : 0;
        $relative = 'storage/backups/' . $filename;
        $logId = $this->logs->create([
            'file_path' => $relative,
            'file_size_bytes' => $size,
            'status' => 'success',
            'triggered_by' => 'manual',
        ]);

        $this->rotate();

        $this->audit->log(
            'backup.create',
            'backup_logs',
            $logId,
            null,
            ['file_path' => $relative, 'file_size_bytes' => $size],
            $staffId,
        );

        return ['ok' => true, 'filename' => $filename, 'size' => $size];
    }

    /**
     * @return array{ok: true}|array{ok: false, error: string}
     */
    public function deleteFile(string $filename, ?int $staffId = null): array
    {
        $safe = $this->safeFilename($filename);
        if ($safe === null) {
            return ['ok' => false, 'error' => 'Invalid backup filename.'];
        }

        $absolute = $this->backupsDirectory() . '/' . $safe;
        if (!is_file($absolute)) {
            return ['ok' => false, 'error' => 'Backup file not found.'];
        }

        if (!@unlink($absolute)) {
            return ['ok' => false, 'error' => 'Could not delete backup file.'];
        }

        $this->audit->log(
            'backup.delete',
            'backup_logs',
            null,
            ['filename' => $safe],
            null,
            $staffId,
        );

        return ['ok' => true];
    }

    public function absolutePath(string $filename): ?string
    {
        $safe = $this->safeFilename($filename);
        if ($safe === null) {
            return null;
        }

        $full = $this->backupsDirectory() . '/' . $safe;
        $real = realpath($full);
        $base = realpath($this->backupsDirectory());
        if ($real === false || $base === false || !str_starts_with($real, $base) || !is_file($real)) {
            return null;
        }

        return $real;
    }

    public function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1048576) {
            return number_format($bytes / 1024, 1) . ' KB';
        }

        return number_format($bytes / 1048576, 2) . ' MB';
    }

    /**
     * Keep newest KEEP_COUNT dump files; delete older ones.
     */
    public function rotate(): int
    {
        $files = $this->listFiles();
        $removed = 0;
        foreach (array_slice($files, self::KEEP_COUNT) as $file) {
            if (@unlink($file['path'])) {
                $removed++;
            }
        }

        return $removed;
    }

    /**
     * @return array{ok: true}|array{ok: false, error: string}
     */
    private function runMysqldump(string $absolutePath): array
    {
        $bin = $this->resolveMysqldump();
        if ($bin === null) {
            return ['ok' => false, 'error' => 'mysqldump was not found. Install MySQL client tools or set MYSQLDUMP_PATH in .env.'];
        }

        $host = (string) Config::database('host');
        $port = (int) Config::database('port');
        $db = (string) Config::database('database');
        $user = (string) Config::database('username');
        $pass = (string) Config::database('password');

        $cmd = [
            $bin,
            '--host=' . $host,
            '--port=' . (string) $port,
            '--user=' . $user,
            '--single-transaction',
            '--routines',
            '--triggers',
            '--databases',
            $db,
            '--result-file=' . $absolutePath,
        ];

        if ($pass !== '') {
            // Avoid shell history exposure where possible; still needed for mysqldump.
            $cmd[] = '--password=' . $pass;
        }

        $descriptor = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = proc_open($cmd, $descriptor, $pipes, null, null, ['bypass_shell' => true]);
        if (!is_resource($process)) {
            return ['ok' => false, 'error' => 'Could not start mysqldump.'];
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]) ?: '';
        $stderr = stream_get_contents($pipes[2]) ?: '';
        fclose($pipes[1]);
        fclose($pipes[2]);
        $code = proc_close($process);

        if ($code !== 0 || !is_file($absolutePath) || filesize($absolutePath) === 0) {
            if (is_file($absolutePath)) {
                @unlink($absolutePath);
            }
            $msg = trim($stderr !== '' ? $stderr : $stdout);
            if ($msg === '') {
                $msg = 'mysqldump exited with code ' . $code;
            }

            return ['ok' => false, 'error' => $msg];
        }

        return ['ok' => true];
    }

    private function resolveMysqldump(): ?string
    {
        $configured = trim((string) (\App\Core\Env::get('MYSQLDUMP_PATH', '') ?? ''));
        if ($configured !== '' && is_file($configured)) {
            return $configured;
        }

        $candidates = [
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            'C:\\xampp\\mysql\\bin\\mysqldump',
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
            'mysqldump',
        ];

        foreach ($candidates as $path) {
            if ($path === 'mysqldump') {
                // Rely on PATH via proc_open when no absolute path works.
                continue;
            }
            if (is_file($path)) {
                return $path;
            }
        }

        // Last resort: bare command name (PATH).
        return 'mysqldump';
    }

    private function safeFilename(string $filename): ?string
    {
        $filename = basename(str_replace('\\', '/', $filename));
        if ($filename === '' || str_contains($filename, '..')) {
            return null;
        }
        if (!preg_match('/^hms_\d{8}_\d{6}\.sql(\.gz)?$/i', $filename)) {
            return null;
        }

        return $filename;
    }
}
