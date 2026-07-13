<?php
/** @var array<string, mixed> $entry */
/** @var \App\Services\AuditService $auditService */
/** @var array<string, mixed>|list<mixed>|null $oldDecoded */
/** @var array<string, mixed>|list<mixed>|null $newDecoded */

$pretty = static function (?array $data): string {
    if ($data === null) {
        return '—';
    }
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    return $json !== false ? $json : '—';
};
?>
<div class="space-y-stack-gap">
    <div>
        <p class="text-body-sm text-on-surface-variant">
            <a href="<?= e(url('/audit')) ?>" class="hover:text-primary">Audit logs</a> / Detail
        </p>
        <h1 class="text-headline-md text-on-surface"><?= e($auditService->labelForAction((string) $entry['action'])) ?></h1>
        <p class="data-mono text-body-sm text-on-surface-variant"><?= e((string) $entry['action']) ?></p>
    </div>

    <section class="surface-card grid gap-4 p-6 sm:grid-cols-2">
        <div>
            <p class="label-caps mb-1 text-outline">When</p>
            <p class="data-mono text-body-sm"><?= e(format_datetime((string) $entry['created_at'])) ?></p>
        </div>
        <div>
            <p class="label-caps mb-1 text-outline">Actor</p>
            <p class="text-body-sm"><?= e((string) ($entry['staff_name'] ?: 'System')) ?></p>
            <?php if (!empty($entry['staff_email'])): ?>
                <p class="text-[11px] text-on-surface-variant"><?= e((string) $entry['staff_email']) ?></p>
            <?php endif; ?>
        </div>
        <div>
            <p class="label-caps mb-1 text-outline">Record</p>
            <p class="data-mono text-body-sm">
                <?php if (!empty($entry['table_name'])): ?>
                    <?= e((string) $entry['table_name']) ?>#<?= (int) ($entry['record_id'] ?? 0) ?>
                <?php else: ?>
                    —
                <?php endif; ?>
            </p>
        </div>
        <div>
            <p class="label-caps mb-1 text-outline">IP address</p>
            <p class="data-mono text-body-sm"><?= e((string) ($entry['ip_address'] ?: '—')) ?></p>
        </div>
    </section>

    <div class="grid gap-stack-gap lg:grid-cols-2">
        <section class="surface-card p-6">
            <h2 class="mb-3 text-title-sm">Before</h2>
            <pre class="overflow-x-auto rounded bg-surface-container-low p-3 data-mono text-[11px] text-on-surface-variant whitespace-pre-wrap"><?= e($pretty($oldDecoded)) ?></pre>
        </section>
        <section class="surface-card p-6">
            <h2 class="mb-3 text-title-sm">After</h2>
            <pre class="overflow-x-auto rounded bg-surface-container-low p-3 data-mono text-[11px] text-on-surface-variant whitespace-pre-wrap"><?= e($pretty($newDecoded)) ?></pre>
        </section>
    </div>
</div>
