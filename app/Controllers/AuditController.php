<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Core\View;
use App\Models\AuditLog;
use App\Models\Staff;
use App\Services\AuditService;

final class AuditController
{
    private AuditLog $logs;
    private AuditService $service;
    private Staff $staff;

    public function __construct(
        ?AuditLog $logs = null,
        ?AuditService $service = null,
        ?Staff $staff = null,
    ) {
        $this->logs = $logs ?? new AuditLog();
        $this->service = $service ?? new AuditService();
        $this->staff = $staff ?? new Staff();
    }

    public function index(Request $request): void
    {
        Auth::requirePermission(\Permission::AUDIT_VIEW);

        $filters = [
            'action' => $this->stringOrNull($request->input('action')),
            'table_name' => $this->stringOrNull($request->input('table_name')),
            'staff_id' => $this->intOrNull($request->input('staff_id')),
            'date_from' => $this->stringOrNull($request->input('date_from')),
            'date_to' => $this->stringOrNull($request->input('date_to')),
            'q' => $this->stringOrNull($request->input('q')),
        ];

        View::render('audit/index', [
            'title' => 'Audit logs',
            'entries' => $this->logs->filtered($filters),
            'filters' => $filters,
            'actions' => $this->logs->distinctActions(),
            'tables' => $this->logs->distinctTables(),
            'staffList' => $this->staff->filtered([], 200),
            'auditService' => $this->service,
        ], 'app');
    }

    public function show(Request $request, string $id): void
    {
        Auth::requirePermission(\Permission::AUDIT_VIEW);

        $entry = $this->logs->findById((int) $id);
        if ($entry === null) {
            Session::flash('error', 'Audit entry not found.');
            redirect('/audit');
        }

        View::render('audit/show', [
            'title' => 'Audit detail',
            'entry' => $entry,
            'auditService' => $this->service,
            'oldDecoded' => $this->decodeJson($entry['old_values'] ?? null),
            'newDecoded' => $this->decodeJson($entry['new_values'] ?? null),
        ], 'app');
    }

    /** @return array<string, mixed>|list<mixed>|null */
    private function decodeJson(mixed $raw): array|null
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $str = trim((string) $value);

        return $str === '' ? null : $str;
    }

    private function intOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
