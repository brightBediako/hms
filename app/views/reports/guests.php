<?php
/** @var array<string, mixed> $data */
/** @var string $from */
/** @var string $to */
/** @var bool $printMode */
$actionPath = '/reports/guests';
?>
<div class="space-y-stack-gap">
    <div>
        <?php if (empty($printMode)): ?>
            <p class="text-body-sm text-on-surface-variant">
                <a href="<?= e(url('/reports')) ?>" class="hover:text-primary">Reports</a> / Guests
            </p>
        <?php else: ?>
            <p class="label-caps text-outline"><?= e(hotel_name()) ?></p>
        <?php endif; ?>
        <h1 class="text-headline-md text-on-surface">Guests report</h1>
        <p class="text-body-sm text-on-surface-variant">
            New profiles <?= e(format_date($data['from'])) ?> – <?= e(format_date($data['to'])) ?>
        </p>
    </div>

    <?php require __DIR__ . '/_range_filter.php'; ?>

    <div class="grid gap-stack-gap sm:grid-cols-2">
        <div class="surface-card p-4">
            <p class="label-caps text-outline">New in range</p>
            <p class="data-mono text-headline-md"><?= (int) $data['new_guests'] ?></p>
        </div>
        <div class="surface-card p-4">
            <p class="label-caps text-outline">Total guests on file</p>
            <p class="data-mono text-headline-md"><?= (int) $data['total_guests'] ?></p>
        </div>
    </div>

    <div class="surface-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-surface-container-low">
                    <tr>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Guest</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Phone</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Created</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline text-right">Stays</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($data['guests'] === []): ?>
                        <tr><td colspan="4" class="px-cell-x py-6 text-center text-body-sm text-on-surface-variant">No new guests in range.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($data['guests'] as $row): ?>
                        <tr class="border-t border-outline-variant">
                            <td class="px-cell-x py-cell-y text-body-sm"><?= e((string) $row['full_name']) ?></td>
                            <td class="px-cell-x py-cell-y data-mono text-body-sm"><?= e((string) ($row['phone'] ?: '—')) ?></td>
                            <td class="px-cell-x py-cell-y data-mono text-[11px]"><?= e(format_datetime((string) $row['created_at'])) ?></td>
                            <td class="px-cell-x py-cell-y data-mono text-right"><?= (int) $row['stays_count'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
