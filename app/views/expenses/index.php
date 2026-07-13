<?php
/** @var list<array<string, mixed>> $expenses */
/** @var list<array<string, mixed>> $categories */
/** @var float $totalAmount */
/** @var array{category_id:?int,date_from:?string,date_to:?string,q:?string} $filters */
/** @var bool $canManage */
?>
<div class="space-y-stack-gap">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-headline-md text-on-surface">Expenses</h1>
            <p class="text-body-sm text-on-surface-variant">
                Utilities, repairs, purchases, and operational costs.
            </p>
        </div>
        <?php if ($canManage): ?>
            <a href="<?= e(url('/expenses/create')) ?>" class="btn-action">
                <span class="material-symbols-outlined text-[18px]">add</span>
                Record expense
            </a>
        <?php endif; ?>
    </div>

    <div class="surface-card flex flex-wrap items-center justify-between gap-3 p-4">
        <div>
            <p class="label-caps text-outline">Filtered total</p>
            <p class="data-mono text-title-sm font-semibold"><?= e(format_money($totalAmount)) ?></p>
        </div>
        <p class="text-body-sm text-on-surface-variant"><?= count($expenses) ?> record(s) shown</p>
    </div>

    <form method="get" action="<?= e(url('/expenses')) ?>" class="flex flex-wrap items-end gap-3">
        <div class="min-w-[180px] flex-1">
            <label class="label-caps mb-1 block text-outline" for="q">Search</label>
            <input id="q" name="q" type="search" class="input-field" placeholder="Description…"
                   value="<?= e((string) ($filters['q'] ?? '')) ?>">
        </div>
        <div>
            <label class="label-caps mb-1 block text-outline" for="category_id">Category</label>
            <select id="category_id" name="category_id" class="input-field">
                <option value="">All</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int) $cat['id'] ?>" <?= (int) ($filters['category_id'] ?? 0) === (int) $cat['id'] ? 'selected' : '' ?>>
                        <?= e((string) $cat['name']) ?>
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
                        <th class="label-caps px-cell-x py-cell-y text-outline">Date</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Category</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Description</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Recorded by</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline text-right">Amount</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($expenses === []): ?>
                        <tr>
                            <td colspan="6" class="px-cell-x py-8 text-center text-body-sm text-on-surface-variant">
                                No expenses match these filters.
                                <?php if ($canManage): ?>
                                    <a class="font-semibold text-primary-container" href="<?= e(url('/expenses/create')) ?>">Record one</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($expenses as $row): ?>
                        <tr class="border-t border-outline-variant hover:bg-primary/[0.02]">
                            <td class="px-cell-x py-cell-y data-mono text-body-sm text-on-surface-variant">
                                <?= e(format_date((string) $row['expense_date'])) ?>
                            </td>
                            <td class="px-cell-x py-cell-y text-body-sm"><?= e((string) $row['category_name']) ?></td>
                            <td class="px-cell-x py-cell-y">
                                <a href="<?= e(url('/expenses/' . $row['id'])) ?>" class="text-body-sm hover:text-primary">
                                    <?= e((string) $row['description']) ?>
                                </a>
                                <?php if (!empty($row['receipt_path'])): ?>
                                    <span class="material-symbols-outlined align-middle text-[14px] text-on-surface-variant" title="Has receipt">attach_file</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-cell-x py-cell-y text-body-sm text-on-surface-variant">
                                <?= e((string) ($row['recorded_by_name'] ?: '—')) ?>
                            </td>
                            <td class="px-cell-x py-cell-y data-mono text-body-sm text-right font-semibold">
                                <?= e(format_money($row['amount'])) ?>
                            </td>
                            <td class="px-cell-x py-cell-y text-right">
                                <a class="btn-ghost" href="<?= e(url('/expenses/' . $row['id'])) ?>">Open</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
