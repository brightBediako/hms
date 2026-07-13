<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Services\BackupService;
use App\Services\SettingsService;

final class SettingsController
{
    private BackupService $backups;
    private SettingsService $settings;

    public function __construct(
        ?BackupService $backups = null,
        ?SettingsService $settings = null,
    ) {
        $this->backups = $backups ?? new BackupService();
        $this->settings = $settings ?? new SettingsService();
    }

    public function index(Request $request): void
    {
        $canSettings = Auth::can(\Permission::SETTINGS_MANAGE);
        $canBackup = Auth::can(\Permission::BACKUP_MANAGE);
        if (!$canSettings && !$canBackup) {
            Auth::requirePermission(\Permission::SETTINGS_MANAGE);
        }

        View::render('settings/index', [
            'title' => 'Settings',
            'canSettings' => $canSettings,
            'canBackup' => $canBackup,
            'preview' => $canSettings ? $this->settings->formDefaults() : null,
        ], 'app');
    }

    public function hotel(Request $request): void
    {
        Auth::requirePermission(\Permission::SETTINGS_MANAGE);

        View::render('settings/hotel', [
            'title' => 'Hotel settings',
            'errors' => Session::pullFlash('errors') ?? [],
            'old' => Session::pullFlash('old') ?? [],
            'defaults' => $this->settings->formDefaults(),
        ], 'app');
    }

    public function hotelUpdate(Request $request): void
    {
        Auth::requirePermission(\Permission::SETTINGS_MANAGE);

        $validator = new Validator();
        $data = $validator->validate($request->post(), [
            'hotel_name' => 'required|max:100',
            'currency' => 'required|max:3',
            'tax_rate_percent' => 'required',
            'check_in_time' => 'required',
            'check_out_time' => 'required',
        ]);

        if ($data === null) {
            Session::flash('errors', $validator->firstErrors());
            Session::flash('old', $request->post());
            redirect('/settings/hotel');
        }

        $result = $this->settings->updateHotelSettings([
            'hotel_name' => (string) $data['hotel_name'],
            'currency' => (string) $data['currency'],
            'tax_rate_percent' => (string) $data['tax_rate_percent'],
            'check_in_time' => (string) $data['check_in_time'],
            'check_out_time' => (string) $data['check_out_time'],
        ], Auth::id());

        if (!$result['ok']) {
            Session::flash('error', $result['error']);
            Session::flash('errors', $result['errors'] ?? []);
            Session::flash('old', $request->post());
            redirect('/settings/hotel');
        }

        Session::flash('success', 'Hotel settings saved.');
        redirect('/settings/hotel');
    }

    public function backups(Request $request): void
    {
        Auth::requirePermission(\Permission::BACKUP_MANAGE);

        View::render('settings/backups', [
            'title' => 'Backups',
            'files' => $this->backups->listFiles(),
            'logs' => $this->backups->recentLogs(20),
            'backupService' => $this->backups,
            'keepCount' => BackupService::KEEP_COUNT,
        ], 'app');
    }

    public function backupCreate(Request $request): void
    {
        Auth::requirePermission(\Permission::BACKUP_MANAGE);

        $result = $this->backups->createManual(Auth::id());
        if (!$result['ok']) {
            Session::flash('error', $result['error']);
        } else {
            Session::flash(
                'success',
                'Backup created: ' . $result['filename'] . ' (' . $this->backups->formatBytes($result['size']) . ').'
            );
        }
        redirect('/settings/backups');
    }

    public function backupDownload(Request $request, string $filename): void
    {
        Auth::requirePermission(\Permission::BACKUP_MANAGE);

        $absolute = $this->backups->absolutePath($filename);
        if ($absolute === null) {
            Session::flash('error', 'Backup file not found.');
            redirect('/settings/backups');
        }

        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . basename($absolute) . '"');
        header('Content-Length: ' . (string) filesize($absolute));
        readfile($absolute);
        exit;
    }

    public function backupDelete(Request $request, string $filename): void
    {
        Auth::requirePermission(\Permission::BACKUP_MANAGE);

        $result = $this->backups->deleteFile($filename, Auth::id());
        Session::flash($result['ok'] ? 'success' : 'error', $result['ok'] ? 'Backup deleted.' : $result['error']);
        redirect('/settings/backups');
    }
}
