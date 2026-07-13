<?php
/** @var list<array{filename: string, path: string, size: int, modified_at: int}> $files */
/** @var list<array<string, mixed>> $logs */
/** @var \App\Services\BackupService $backupService */
/** @var int $keepCount */
?>
<div class="space-y-stack-gap">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="text-body-sm text-on-surface-variant">
                <a href="<?= e(url('/settings')) ?>" class="hover:text-primary">Settings</a> / Backups
            </p>
            <h1 class="text-headline-md text-on-surface">Database backups</h1>
            <p class="text-body-sm text-on-surface-variant">
                Manual dumps to <span class="data-mono">storage/backups</span>. Newest <?= (int) $keepCount ?> files are kept.
            </p>
        </div>
        <form method="post" action="<?= e(url('/settings/backups')) ?>">
            <?= \App\Core\CSRF::field() ?>
            <button type="submit" class="btn-action">
                <span class="material-symbols-outlined text-[18px]">backup</span>
                Create backup
            </button>
        </form>
    </div>

    <div class="surface-card space-y-3 p-4">
        <h2 class="text-title-sm">Restore procedure</h2>
        <p class="text-body-sm text-on-surface-variant">
            Restore is intentionally a documented offline step (not a one-click UI action),
            to avoid accidental overwrite of a live database.
        </p>
        <ol class="list-decimal space-y-1 pl-5 text-body-sm text-on-surface-variant">
            <li>Download the <span class="data-mono">.sql</span> dump from this screen.</li>
            <li>Stop traffic to HMS (or put the site in maintenance).</li>
            <li>From a shell, run the steps in <span class="data-mono">docs/restore.md</span>.</li>
            <li>Verify login and a sample reservation/invoice after restore.</li>
        </ol>
        <p class="text-[11px] text-outline">Full guide: <span class="data-mono">docs/restore.md</span></p>
    </div>

    <div class="surface-card overflow-hidden">
        <div class="border-b border-outline-variant px-4 py-3">
            <h2 class="text-title-sm">Backup files</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-surface-container-low">
                    <tr>
                        <th class="label-caps px-cell-x py-cell-y text-outline">File</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Created</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline text-right">Size</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($files === []): ?>
                        <tr>
                            <td colspan="4" class="px-cell-x py-8 text-center text-body-sm text-on-surface-variant">
                                No backup files yet. Create the first dump.
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($files as $file): ?>
                        <tr class="border-t border-outline-variant">
                            <td class="px-cell-x py-cell-y data-mono text-body-sm"><?= e($file['filename']) ?></td>
                            <td class="px-cell-x py-cell-y data-mono text-[11px] text-on-surface-variant">
                                <?= e(date('d M Y H:i', $file['modified_at'])) ?>
                            </td>
                            <td class="px-cell-x py-cell-y data-mono text-right text-body-sm">
                                <?= e($backupService->formatBytes($file['size'])) ?>
                            </td>
                            <td class="px-cell-x py-cell-y text-right">
                                <a class="btn-ghost" href="<?= e(url('/settings/backups/' . rawurlencode($file['filename']) . '/download')) ?>">Download</a>
                                <form method="post" action="<?= e(url('/settings/backups/' . rawurlencode($file['filename']) . '/delete')) ?>"
                                      class="inline" onsubmit="return confirm('Delete this backup file?');">
                                    <?= \App\Core\CSRF::field() ?>
                                    <button type="submit" class="btn-ghost text-error">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="surface-card overflow-hidden">
        <div class="border-b border-outline-variant px-4 py-3">
            <h2 class="text-title-sm">Backup log</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-surface-container-low">
                    <tr>
                        <th class="label-caps px-cell-x py-cell-y text-outline">When</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Status</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Trigger</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Path</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline text-right">Size</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($logs === []): ?>
                        <tr>
                            <td colspan="5" class="px-cell-x py-6 text-center text-body-sm text-on-surface-variant">No backup log rows yet.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($logs as $row): ?>
                        <tr class="border-t border-outline-variant">
                            <td class="px-cell-x py-cell-y data-mono text-[11px]"><?= e(format_datetime((string) $row['created_at'])) ?></td>
                            <td class="px-cell-x py-cell-y text-body-sm"><?= e(ucfirst((string) $row['status'])) ?></td>
                            <td class="px-cell-x py-cell-y text-body-sm"><?= e(ucfirst((string) $row['triggered_by'])) ?></td>
                            <td class="px-cell-x py-cell-y data-mono text-[11px] text-on-surface-variant"><?= e((string) $row['file_path']) ?></td>
                            <td class="px-cell-x py-cell-y data-mono text-right text-body-sm">
                                <?= $row['file_size_bytes'] !== null ? e($backupService->formatBytes((int) $row['file_size_bytes'])) : '—' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
