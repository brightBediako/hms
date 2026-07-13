<?php
/** @var list<array<string, mixed>> $entries */
/** @var array{action:?string,table_name:?string,staff_id:?int,date_from:?string,date_to:?string,q:?string} $filters */
/** @var list<string> $actions */
/** @var list<string> $tables */
/** @var list<array<string, mixed>> $staffList */
/** @var \App\Services\AuditService $auditService */
?>
<div class="space-y-stack-gap">
    <div>
        <h1 class="text-headline-md text-on-surface">Audit logs</h1>
        <p class="text-body-sm text-on-surface-variant">
            Read-only chronology of sensitive create/update/delete actions.
        </p>
    </div>

    <form method="get" action="<?= e(url('/audit')) ?>" class="flex flex-wrap items-end gap-3">
        <div class="min-w-[160px] flex-1">
            <label class="label-caps mb-1 block text-outline" for="q">Search</label>
            <input id="q" name="q" type="search" class="input-field" placeholder="Action, actor, record…"
                   value="<?= e((string) ($filters['q'] ?? '')) ?>">
        </div>
        <div>
            <label class="label-caps mb-1 block text-outline" for="action">Action</label>
            <select id="action" name="action" class="input-field">
                <option value="">All</option>
                <?php foreach ($actions as $action): ?>
                    <option value="<?= e($action) ?>" <?= ($filters['action'] ?? '') === $action ? 'selected' : '' ?>>
                        <?= e($auditService->labelForAction($action)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="label-caps mb-1 block text-outline" for="table_name">Table</label>
            <select id="table_name" name="table_name" class="input-field">
                <option value="">All</option>
                <?php foreach ($tables as $table): ?>
                    <option value="<?= e($table) ?>" <?= ($filters['table_name'] ?? '') === $table ? 'selected' : '' ?>>
                        <?= e($table) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="label-caps mb-1 block text-outline" for="staff_id">Actor</label>
            <select id="staff_id" name="staff_id" class="input-field">
                <option value="">All</option>
                <?php foreach ($staffList as $member): ?>
                    <option value="<?= (int) $member['id'] ?>" <?= (int) ($filters['staff_id'] ?? 0) === (int) $member['id'] ? 'selected' : '' ?>>
                        <?= e((string) $member['full_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="label-caps mb-1 block text-outline" for="date_from">From</label>
            <input id="date_from" name="date_from" type="date" class="input-field"
                   value="<?= e((string) ($filters['date_from'] ?? '')) ?>">
        </div>
        <div>
            <label class="label-caps mb-1 block text-outline" for="date_to">To</label>
            <input id="date_to" name="date_to" type="date" class="input-field"
                   value="<?= e((string) ($filters['date_to'] ?? '')) ?>">
        </div>
        <button type="submit" class="btn-primary">Filter</button>
    </form>

    <div class="surface-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-surface-container-low">
                    <tr>
                        <th class="label-caps px-cell-x py-cell-y text-outline">When</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Actor</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Action</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Record</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">IP</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($entries === []): ?>
                        <tr>
                            <td colspan="6" class="px-cell-x py-8 text-center text-body-sm text-on-surface-variant">
                                No audit entries match these filters.
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($entries as $row): ?>
                        <tr class="border-t border-outline-variant hover:bg-primary/[0.02]">
                            <td class="px-cell-x py-cell-y data-mono text-[11px] text-on-surface-variant">
                                <?= e(format_datetime((string) $row['created_at'])) ?>
                            </td>
                            <td class="px-cell-x py-cell-y text-body-sm">
                                <?= e((string) ($row['staff_name'] ?: 'System')) ?>
                            </td>
                            <td class="px-cell-x py-cell-y">
                                <p class="text-body-sm"><?= e($auditService->labelForAction((string) $row['action'])) ?></p>
                                <p class="data-mono text-[10px] text-outline"><?= e((string) $row['action']) ?></p>
                            </td>
                            <td class="px-cell-x py-cell-y data-mono text-body-sm text-on-surface-variant">
                                <?php if (!empty($row['table_name'])): ?>
                                    <?= e((string) $row['table_name']) ?>#<?= (int) ($row['record_id'] ?? 0) ?>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td class="px-cell-x py-cell-y data-mono text-[11px] text-on-surface-variant">
                                <?= e((string) ($row['ip_address'] ?: '—')) ?>
                            </td>
                            <td class="px-cell-x py-cell-y text-right">
                                <a class="btn-ghost" href="<?= e(url('/audit/' . $row['id'])) ?>">Detail</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
