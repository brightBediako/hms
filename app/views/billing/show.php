<?php
/** @var array<string, mixed> $invoice */
/** @var list<array<string, mixed>> $items */
/** @var list<array<string, mixed>> $payments */
/** @var bool $canCreate */
/** @var bool $canVoid */
/** @var bool $canRecordPayment */
/** @var \App\Services\BillingService $billingService */
/** @var \App\Services\PaymentService $paymentService */
/** @var array<string, string> $itemErrors */
/** @var array<string, mixed> $itemOld */
/** @var array<string, string> $paymentErrors */
/** @var array<string, mixed> $paymentOld */

$chip = $billingService->chipClasses((string) $invoice['status']);
$isDraft = $invoice['status'] === 'draft';
$canEditLines = $canCreate && in_array($invoice['status'], ['draft', 'issued', 'partially_paid'], true);
$canPay = $canRecordPayment
    && in_array($invoice['status'], ['issued', 'partially_paid'], true)
    && (float) $invoice['balance_due'] > 0;
?>
<div class="space-y-stack-gap">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <nav class="flex items-center gap-2 text-body-sm text-on-surface-variant">
            <a href="<?= e(url('/billing')) ?>" class="hover:text-primary">Invoices</a>
            <span class="material-symbols-outlined text-[14px]">chevron_right</span>
            <span class="data-mono text-on-surface"><?= e((string) $invoice['invoice_number']) ?></span>
        </nav>
        <div class="flex flex-wrap gap-2">
            <a href="<?= e(url('/billing/' . $invoice['id'] . '/print')) ?>" class="btn-outline" target="_blank">
                <span class="material-symbols-outlined text-[18px]">print</span>
                Print
            </a>
            <?php if ($isDraft && $canCreate): ?>
                <form method="post" action="<?= e(url('/billing/' . $invoice['id'] . '/issue')) ?>">
                    <?= \App\Core\CSRF::field() ?>
                    <button type="submit" class="btn-action">Issue invoice</button>
                </form>
            <?php endif; ?>
            <?php if ($canVoid && $invoice['status'] !== 'void' && (float) $invoice['amount_paid'] <= 0): ?>
                <form method="post" action="<?= e(url('/billing/' . $invoice['id'] . '/void')) ?>"
                      onsubmit="return confirm('Void this invoice?');">
                    <?= \App\Core\CSRF::field() ?>
                    <button type="submit" class="btn-outline border-error text-error">Void</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="surface-card relative overflow-hidden p-6">
        <div class="absolute left-0 top-0 h-full w-1.5 bg-primary"></div>
        <div class="grid gap-6 md:grid-cols-3">
            <div>
                <p class="label-caps mb-1 text-outline">Guest details</p>
                <h2 class="text-title-sm text-on-surface"><?= e((string) $invoice['guest_name']) ?></h2>
                <p class="text-body-sm text-on-surface-variant">
                    <?= e((string) $invoice['room_type_name']) ?>, Room
                    <span class="data-mono">#<?= e((string) $invoice['room_number']) ?></span>
                </p>
                <p class="text-body-sm text-on-surface-variant"><?= e((string) ($invoice['guest_email'] ?: $invoice['guest_phone'] ?: '—')) ?></p>
                <p class="mt-2 data-mono text-[11px] text-on-surface-variant">
                    <?= e((string) $invoice['booking_reference']) ?>
                </p>
            </div>
            <div>
                <p class="label-caps mb-1 text-outline">Invoice status</p>
                <span class="inline-block rounded-sm px-2 py-0.5 text-[10px] font-bold uppercase <?= e($chip['bg']) ?> <?= e($chip['text']) ?>">
                    <?= e($billingService->labelForStatus((string) $invoice['status'])) ?>
                </span>
                <p class="label-caps mb-1 mt-4 text-outline">Stay dates</p>
                <p class="data-mono text-body-sm">
                    <?= e(format_date((string) $invoice['check_in_date'])) ?>
                    –
                    <?= e(format_date((string) $invoice['check_out_date'])) ?>
                </p>
            </div>
            <div class="text-right">
                <p class="label-caps mb-1 text-outline">Balance due</p>
                <p class="data-mono text-display-lg text-primary"><?= e(format_money($invoice['balance_due'])) ?></p>
                <p class="mt-2 text-body-sm text-on-surface-variant">
                    Total <?= e(format_money($invoice['total_amount'])) ?>
                    · Paid <?= e(format_money($invoice['amount_paid'])) ?>
                </p>
            </div>
        </div>
    </div>

    <div class="surface-card overflow-hidden">
        <div class="border-b border-outline-variant bg-surface-container-low px-4 py-2">
            <p class="label-caps text-on-surface-variant">Line items &amp; services</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-surface-container-low/50">
                    <tr>
                        <th class="label-caps px-4 py-3 text-outline">Type</th>
                        <th class="label-caps px-4 py-3 text-outline">Description</th>
                        <th class="label-caps px-4 py-3 text-outline text-right">Qty</th>
                        <th class="label-caps px-4 py-3 text-outline text-right">Unit</th>
                        <th class="label-caps px-4 py-3 text-outline text-right">Total</th>
                        <?php if ($isDraft && $canCreate): ?>
                            <th class="label-caps px-4 py-3 text-outline"></th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($items === []): ?>
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-body-sm text-on-surface-variant">No line items.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($items as $item): ?>
                        <tr class="border-t border-outline-variant hover:bg-primary/[0.02]">
                            <td class="px-4 py-cell-y text-body-sm text-on-surface-variant">
                                <?= e($billingService->labelForItemType((string) $item['item_type'])) ?>
                            </td>
                            <td class="px-4 py-cell-y text-body-sm"><?= e((string) $item['description']) ?></td>
                            <td class="px-4 py-cell-y data-mono text-body-sm text-right"><?= e((string) $item['quantity']) ?></td>
                            <td class="px-4 py-cell-y data-mono text-body-sm text-right"><?= e(format_money($item['unit_price'])) ?></td>
                            <td class="px-4 py-cell-y data-mono text-body-sm text-right font-semibold"><?= e(format_money($item['line_total'])) ?></td>
                            <?php if ($isDraft && $canCreate): ?>
                                <td class="px-4 py-cell-y text-right">
                                    <form method="post" action="<?= e(url('/billing/' . $invoice['id'] . '/items/' . $item['id'] . '/delete')) ?>"
                                          onsubmit="return confirm('Remove this line?');">
                                        <?= \App\Core\CSRF::field() ?>
                                        <button type="submit" class="btn-ghost text-error">Remove</button>
                                    </form>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="grid gap-2 border-t border-outline-variant bg-surface-container-low/40 px-4 py-4 sm:grid-cols-2">
            <div></div>
            <div class="space-y-1 text-body-sm">
                <div class="flex justify-between">
                    <span class="text-on-surface-variant">Subtotal</span>
                    <span class="data-mono"><?= e(format_money($invoice['subtotal'])) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-on-surface-variant">Discount</span>
                    <span class="data-mono">−<?= e(format_money($invoice['discount_amount'])) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-on-surface-variant">Tax</span>
                    <span class="data-mono"><?= e(format_money($invoice['tax_amount'])) ?></span>
                </div>
                <div class="flex justify-between border-t border-outline-variant pt-2 text-title-sm">
                    <span>Total</span>
                    <span class="data-mono text-primary"><?= e(format_money($invoice['total_amount'])) ?></span>
                </div>
            </div>
        </div>
    </div>

    <?php if ($canEditLines): ?>
        <form method="post" action="<?= e(url('/billing/' . $invoice['id'] . '/items')) ?>" class="surface-card space-y-4 p-6">
            <?= \App\Core\CSRF::field() ?>
            <h3 class="text-title-sm text-on-surface">Add line item</h3>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label-caps mb-1 block text-outline" for="item_type">Type</label>
                    <select id="item_type" name="item_type" class="input-field" required>
                        <?php foreach (\App\Models\InvoiceItem::TYPES as $type): ?>
                            <?php if ($type === 'room_charge') {
                                continue;
                            } ?>
                            <option value="<?= e($type) ?>" <?= ($itemOld['item_type'] ?? 'service') === $type ? 'selected' : '' ?>>
                                <?= e($billingService->labelForItemType($type)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="label-caps mb-1 block text-outline" for="description">Description</label>
                    <input id="description" name="description" class="input-field" required maxlength="255"
                           value="<?= e((string) ($itemOld['description'] ?? '')) ?>">
                    <?php if (!empty($itemErrors['description'])): ?>
                        <p class="mt-1 text-body-sm text-error"><?= e($itemErrors['description']) ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <label class="label-caps mb-1 block text-outline" for="quantity">Quantity</label>
                    <input id="quantity" name="quantity" type="number" step="0.01" min="0.01" class="input-field data-mono" required
                           value="<?= e((string) ($itemOld['quantity'] ?? '1')) ?>">
                </div>
                <div>
                    <label class="label-caps mb-1 block text-outline" for="unit_price">Unit price</label>
                    <input id="unit_price" name="unit_price" type="number" step="0.01" min="0" class="input-field data-mono" required
                           value="<?= e((string) ($itemOld['unit_price'] ?? '')) ?>">
                </div>
            </div>
            <p class="text-[11px] text-on-surface-variant">
                Discount and tax amounts are stored as positive values and applied in the totals section.
            </p>
            <button type="submit" class="btn-primary">Add item</button>
        </form>
    <?php endif; ?>

    <section class="surface-card overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-outline-variant bg-surface-container-low px-4 py-2">
            <p class="label-caps text-on-surface-variant">Payments</p>
            <?php if ($canPay): ?>
                <a href="<?= e(url('/payments/create?invoice_id=' . $invoice['id'])) ?>" class="btn-action !py-1 text-[11px]">
                    Record payment
                </a>
            <?php endif; ?>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-surface-container-low/50">
                    <tr>
                        <th class="label-caps px-4 py-2 text-outline">When</th>
                        <th class="label-caps px-4 py-2 text-outline">Method</th>
                        <th class="label-caps px-4 py-2 text-outline">Reference</th>
                        <th class="label-caps px-4 py-2 text-outline">Received by</th>
                        <th class="label-caps px-4 py-2 text-right text-outline">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($payments === []): ?>
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-body-sm text-on-surface-variant">
                                No payments recorded yet.
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($payments as $payment): ?>
                        <tr class="border-t border-outline-variant">
                            <td class="px-4 py-cell-y data-mono text-body-sm text-on-surface-variant">
                                <?= e(format_datetime((string) $payment['paid_at'])) ?>
                            </td>
                            <td class="px-4 py-cell-y text-body-sm">
                                <?= e($paymentService->labelForMethod((string) $payment['method'])) ?>
                            </td>
                            <td class="px-4 py-cell-y data-mono text-body-sm text-on-surface-variant">
                                <?= e((string) ($payment['reference_number'] ?: '—')) ?>
                            </td>
                            <td class="px-4 py-cell-y text-body-sm text-on-surface-variant">
                                <?= e((string) ($payment['received_by_name'] ?: '—')) ?>
                            </td>
                            <td class="px-4 py-cell-y data-mono text-body-sm text-right font-semibold">
                                <?= e(format_money($payment['amount'])) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($canPay): ?>
            <form method="post" action="<?= e(url('/payments')) ?>" class="space-y-3 border-t border-outline-variant p-4">
                <?= \App\Core\CSRF::field() ?>
                <input type="hidden" name="invoice_id" value="<?= (int) $invoice['id'] ?>">
                <input type="hidden" name="return_to" value="invoice">
                <h3 class="text-title-sm text-on-surface">Quick payment</h3>
                <div class="grid gap-3 sm:grid-cols-4">
                    <div>
                        <label class="label-caps mb-1 block text-outline" for="method">Method</label>
                        <select id="method" name="method" class="input-field" required>
                            <?php foreach (\App\Models\Payment::METHODS as $method): ?>
                                <option value="<?= e($method) ?>" <?= ($paymentOld['method'] ?? 'cash') === $method ? 'selected' : '' ?>>
                                    <?= e($paymentService->labelForMethod($method)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="label-caps mb-1 block text-outline" for="amount">Amount</label>
                        <input id="amount" name="amount" type="number" step="0.01" min="0.01"
                               max="<?= e((string) $invoice['balance_due']) ?>"
                               class="input-field data-mono" required
                               value="<?= e((string) ($paymentOld['amount'] ?? $invoice['balance_due'])) ?>">
                        <?php if (!empty($paymentErrors['amount'])): ?>
                            <p class="mt-1 text-body-sm text-error"><?= e($paymentErrors['amount']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label class="label-caps mb-1 block text-outline" for="reference_number">Reference</label>
                        <input id="reference_number" name="reference_number" class="input-field data-mono" maxlength="100"
                               placeholder="MoMo / card auth"
                               value="<?= e((string) ($paymentOld['reference_number'] ?? '')) ?>">
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="btn-action w-full">Record</button>
                    </div>
                </div>
                <p class="text-[11px] text-on-surface-variant">
                    Balance due <?= e(format_money($invoice['balance_due'])) ?>. Partial payments are allowed.
                </p>
            </form>
        <?php endif; ?>
    </section>

    <p class="text-body-sm text-on-surface-variant">
        <a class="font-semibold text-primary-container" href="<?= e(url('/reservations/' . $invoice['reservation_id'])) ?>">View reservation</a>
        ·
        <a class="font-semibold text-primary-container" href="<?= e(url('/payments')) ?>">All payments</a>
    </p>
</div>
