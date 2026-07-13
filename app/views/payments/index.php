<?php
/** @var list<array<string, mixed>> $payments */
/** @var array{method:?string,q:?string} $filters */
/** @var \App\Services\PaymentService $paymentService */
?>
<div class="space-y-stack-gap">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-headline-md text-on-surface">Payments</h1>
            <p class="text-body-sm text-on-surface-variant">
                Cash, mobile money, and card receipts linked to invoices.
            </p>
        </div>
        <a href="<?= e(url('/payments/create')) ?>" class="btn-action">
            <span class="material-symbols-outlined text-[18px]">add</span>
            Record payment
        </a>
    </div>

    <form method="get" action="<?= e(url('/payments')) ?>" class="flex flex-wrap items-end gap-3">
        <div class="min-w-[200px] flex-1">
            <label class="label-caps mb-1 block text-outline" for="q">Search</label>
            <input id="q" name="q" type="search" class="input-field" placeholder="Invoice #, guest, reference…"
                   value="<?= e((string) ($filters['q'] ?? '')) ?>">
        </div>
        <div>
            <label class="label-caps mb-1 block text-outline" for="method">Method</label>
            <select id="method" name="method" class="input-field">
                <option value="">All</option>
                <?php foreach (\App\Models\Payment::METHODS as $method): ?>
                    <option value="<?= e($method) ?>" <?= ($filters['method'] ?? '') === $method ? 'selected' : '' ?>>
                        <?= e($paymentService->labelForMethod($method)) ?>
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
                        <th class="label-caps px-cell-x py-cell-y text-outline">Paid at</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Invoice</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Guest</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Method</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Reference</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($payments === []): ?>
                        <tr>
                            <td colspan="6" class="px-cell-x py-8 text-center text-body-sm text-on-surface-variant">
                                No payments yet.
                                <a class="font-semibold text-primary-container" href="<?= e(url('/payments/create')) ?>">Record the first payment</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($payments as $row): ?>
                        <tr class="border-t border-outline-variant hover:bg-primary/[0.02]">
                            <td class="px-cell-x py-cell-y data-mono text-body-sm text-on-surface-variant">
                                <?= e(format_datetime((string) $row['paid_at'])) ?>
                            </td>
                            <td class="px-cell-x py-cell-y">
                                <a class="data-mono text-body-sm hover:text-primary" href="<?= e(url('/billing/' . $row['invoice_id'])) ?>">
                                    <?= e((string) $row['invoice_number']) ?>
                                </a>
                            </td>
                            <td class="px-cell-x py-cell-y text-body-sm"><?= e((string) $row['guest_name']) ?></td>
                            <td class="px-cell-x py-cell-y text-body-sm">
                                <?= e($paymentService->labelForMethod((string) $row['method'])) ?>
                            </td>
                            <td class="px-cell-x py-cell-y data-mono text-body-sm text-on-surface-variant">
                                <?= e((string) ($row['reference_number'] ?: '—')) ?>
                            </td>
                            <td class="px-cell-x py-cell-y data-mono text-body-sm text-right font-semibold">
                                <?= e(format_money($row['amount'])) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
