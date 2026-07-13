<?php
/** @var array<string, mixed> $data */
/** @var string $from */
/** @var string $to */
/** @var bool $printMode */
$actionPath = '/reports/profit';
?>
<div class="space-y-stack-gap">
    <div>
        <?php if (empty($printMode)): ?>
            <p class="text-body-sm text-on-surface-variant">
                <a href="<?= e(url('/reports')) ?>" class="hover:text-primary">Reports</a> / Profit
            </p>
        <?php else: ?>
            <p class="label-caps text-outline"><?= e(hotel_name()) ?></p>
        <?php endif; ?>
        <h1 class="text-headline-md text-on-surface">Profit summary</h1>
        <p class="text-body-sm text-on-surface-variant">
            <?= e(format_date($data['from'])) ?> – <?= e(format_date($data['to'])) ?>
        </p>
    </div>

    <?php require __DIR__ . '/_range_filter.php'; ?>

    <div class="grid gap-stack-gap sm:grid-cols-2 lg:grid-cols-4">
        <div class="surface-card p-4">
            <p class="label-caps text-outline">Revenue (payments)</p>
            <p class="data-mono text-headline-md"><?= e(format_money($data['revenue'])) ?></p>
        </div>
        <div class="surface-card p-4">
            <p class="label-caps text-outline">Expenses</p>
            <p class="data-mono text-headline-md"><?= e(format_money($data['expenses'])) ?></p>
        </div>
        <div class="surface-card p-4">
            <p class="label-caps text-outline">Profit</p>
            <p class="data-mono text-headline-md font-semibold <?= ((float) $data['profit']) < 0 ? 'text-error' : 'text-primary' ?>">
                <?= e(format_money($data['profit'])) ?>
            </p>
        </div>
        <div class="surface-card p-4">
            <p class="label-caps text-outline">Outstanding balances</p>
            <p class="data-mono text-headline-md"><?= e(format_money($data['outstanding'])) ?></p>
            <p class="text-[11px] text-on-surface-variant">Current (not period-limited)</p>
        </div>
    </div>

    <p class="text-body-sm text-on-surface-variant">
        Profit = payments collected in range − expenses recorded in range. Outstanding folio balances are shown as a live AR snapshot.
    </p>
</div>
