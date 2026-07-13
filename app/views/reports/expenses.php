<?php
/** @var array<string, mixed> $data */
/** @var string $from */
/** @var string $to */
/** @var bool $printMode */
$actionPath = '/reports/expenses';
?>
<div class="space-y-stack-gap">
    <div>
        <?php if (empty($printMode)): ?>
            <p class="text-body-sm text-on-surface-variant">
                <a href="<?= e(url('/reports')) ?>" class="hover:text-primary">Reports</a> / Expenses
            </p>
        <?php else: ?>
            <p class="label-caps text-outline"><?= e(hotel_name()) ?></p>
        <?php endif; ?>
        <h1 class="text-headline-md text-on-surface">Expenses report</h1>
        <p class="text-body-sm text-on-surface-variant">
            <?= e(format_date($data['from'])) ?> – <?= e(format_date($data['to'])) ?>
        </p>
    </div>

    <?php require __DIR__ . '/_range_filter.php'; ?>

    <div class="surface-card p-4">
        <p class="label-caps text-outline">Total spend</p>
        <p class="data-mono text-headline-md font-semibold"><?= e(format_money($data['total'])) ?></p>
    </div>

    <div class="grid gap-stack-gap lg:grid-cols-2">
        <div class="surface-card overflow-hidden">
            <div class="border-b border-outline-variant px-4 py-3"><h2 class="text-title-sm">By category</h2></div>
            <table class="w-full text-left">
                <thead class="bg-surface-container-low">
                    <tr>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Category</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline text-right">Count</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($data['by_category'] === []): ?>
                        <tr><td colspan="3" class="px-cell-x py-6 text-center text-body-sm text-on-surface-variant">No expenses in range.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($data['by_category'] as $row): ?>
                        <tr class="border-t border-outline-variant">
                            <td class="px-cell-x py-cell-y text-body-sm"><?= e((string) $row['category']) ?></td>
                            <td class="px-cell-x py-cell-y data-mono text-right"><?= (int) $row['count'] ?></td>
                            <td class="px-cell-x py-cell-y data-mono text-right font-semibold"><?= e(format_money($row['total'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="surface-card overflow-hidden">
            <div class="border-b border-outline-variant px-4 py-3"><h2 class="text-title-sm">Detail</h2></div>
            <div class="max-h-96 overflow-auto">
                <table class="w-full text-left">
                    <thead class="sticky top-0 bg-surface-container-low">
                        <tr>
                            <th class="label-caps px-cell-x py-cell-y text-outline">Date</th>
                            <th class="label-caps px-cell-x py-cell-y text-outline">Description</th>
                            <th class="label-caps px-cell-x py-cell-y text-outline text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['expenses'] as $row): ?>
                            <tr class="border-t border-outline-variant">
                                <td class="px-cell-x py-cell-y data-mono text-[11px]"><?= e(format_date((string) $row['expense_date'])) ?></td>
                                <td class="px-cell-x py-cell-y text-body-sm">
                                    <?= e((string) $row['description']) ?>
                                    <p class="text-[11px] text-on-surface-variant"><?= e((string) $row['category_name']) ?></p>
                                </td>
                                <td class="px-cell-x py-cell-y data-mono text-right"><?= e(format_money($row['amount'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
