<?php
/** @var list<array<string, mixed>> $invoices */
/** @var array{status:?string,q:?string} $filters */
/** @var bool $canCreate */
/** @var \App\Services\BillingService $billingService */
?>
<div class="space-y-stack-gap">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-headline-md text-on-surface">Billing &amp; Invoices</h1>
            <p class="text-body-sm text-on-surface-variant">
                Folios generated from stays — room charges, extras, discounts, and tax.
            </p>
        </div>
        <?php if ($canCreate): ?>
            <a href="<?= e(url('/billing/create')) ?>" class="btn-action">
                <span class="material-symbols-outlined text-[18px]">receipt_long</span>
                Generate invoice
            </a>
        <?php endif; ?>
    </div>

    <form method="get" action="<?= e(url('/billing')) ?>" class="flex flex-wrap items-end gap-3">
        <div class="min-w-[200px] flex-1">
            <label class="label-caps mb-1 block text-outline" for="q">Search</label>
            <input id="q" name="q" type="search" class="input-field" placeholder="Invoice #, guest, booking…"
                   value="<?= e((string) ($filters['q'] ?? '')) ?>">
        </div>
        <div>
            <label class="label-caps mb-1 block text-outline" for="status">Status</label>
            <select id="status" name="status" class="input-field">
                <option value="">All</option>
                <?php foreach (\App\Models\Invoice::STATUSES as $status): ?>
                    <option value="<?= e($status) ?>" <?= ($filters['status'] ?? '') === $status ? 'selected' : '' ?>>
                        <?= e($billingService->labelForStatus($status)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn-primary">Filter</button>
    </form>

    <div class="surface-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-surface-container-low">
                    <tr>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Invoice</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Guest</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Stay</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Status</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline text-right">Total</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline text-right">Balance</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($invoices === []): ?>
                        <tr>
                            <td colspan="7" class="px-cell-x py-8 text-center text-body-sm text-on-surface-variant">
                                No invoices yet.
                                <?php if ($canCreate): ?>
                                    <a class="font-semibold text-primary-container" href="<?= e(url('/billing/create')) ?>">Generate from a stay</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($invoices as $row): ?>
                        <?php $chip = $billingService->chipClasses((string) $row['status']); ?>
                        <tr class="border-t border-outline-variant hover:bg-primary/[0.02]">
                            <td class="px-cell-x py-cell-y">
                                <a class="data-mono text-body-sm hover:text-primary" href="<?= e(url('/billing/' . $row['id'])) ?>">
                                    <?= e((string) $row['invoice_number']) ?>
                                </a>
                            </td>
                            <td class="px-cell-x py-cell-y text-body-sm">
                                <?= e((string) $row['guest_name']) ?>
                                <p class="data-mono text-[10px] text-on-surface-variant"><?= e((string) ($row['guest_phone'] ?: '')) ?></p>
                            </td>
                            <td class="px-cell-x py-cell-y text-body-sm text-on-surface-variant">
                                <span class="data-mono"><?= e((string) $row['booking_reference']) ?></span>
                                · #<?= e((string) $row['room_number']) ?>
                            </td>
                            <td class="px-cell-x py-cell-y">
                                <span class="inline-block rounded-sm px-2 py-0.5 text-[10px] font-bold uppercase <?= e($chip['bg']) ?> <?= e($chip['text']) ?>">
                                    <?= e($billingService->labelForStatus((string) $row['status'])) ?>
                                </span>
                            </td>
                            <td class="px-cell-x py-cell-y data-mono text-body-sm text-right"><?= e(format_money($row['total_amount'])) ?></td>
                            <td class="px-cell-x py-cell-y data-mono text-body-sm text-right font-semibold text-primary">
                                <?= e(format_money($row['balance_due'])) ?>
                            </td>
                            <td class="px-cell-x py-cell-y text-right">
                                <a class="btn-ghost" href="<?= e(url('/billing/' . $row['id'])) ?>">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
