<?php
/** @var array<string, mixed> $invoice */
/** @var list<array<string, mixed>> $items */
/** @var list<array<string, mixed>> $payments */
/** @var \App\Services\BillingService $billingService */
/** @var \App\Services\PaymentService $paymentService */
?>
<div class="space-y-6">
    <div class="flex items-start justify-between gap-4 border-b border-outline-variant pb-4">
        <div>
            <h1 class="text-headline-md text-primary"><?= e(hotel_name()) ?></h1>
            <p class="text-body-sm text-on-surface-variant">Guest folio / invoice</p>
        </div>
        <div class="text-right">
            <p class="data-mono text-title-sm"><?= e((string) $invoice['invoice_number']) ?></p>
            <p class="text-body-sm text-on-surface-variant">
                <?= e($billingService->labelForStatus((string) $invoice['status'])) ?>
            </p>
            <?php if (!empty($invoice['issued_at'])): ?>
                <p class="text-[11px] text-on-surface-variant">Issued <?= e(format_datetime((string) $invoice['issued_at'])) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="grid gap-6 sm:grid-cols-2">
        <div>
            <p class="label-caps mb-1 text-outline">Bill to</p>
            <p class="text-title-sm"><?= e((string) $invoice['guest_name']) ?></p>
            <p class="text-body-sm text-on-surface-variant"><?= e((string) ($invoice['guest_email'] ?: '—')) ?></p>
            <p class="text-body-sm text-on-surface-variant"><?= e((string) ($invoice['guest_phone'] ?: '—')) ?></p>
            <?php if (!empty($invoice['guest_address'])): ?>
                <p class="text-body-sm text-on-surface-variant"><?= e((string) $invoice['guest_address']) ?></p>
            <?php endif; ?>
        </div>
        <div>
            <p class="label-caps mb-1 text-outline">Stay</p>
            <p class="text-body-sm">
                <?= e((string) $invoice['room_type_name']) ?>
                · Room <span class="data-mono">#<?= e((string) $invoice['room_number']) ?></span>
            </p>
            <p class="data-mono text-body-sm text-on-surface-variant">
                <?= e(format_date((string) $invoice['check_in_date'])) ?>
                –
                <?= e(format_date((string) $invoice['check_out_date'])) ?>
            </p>
            <p class="data-mono text-[11px] text-on-surface-variant"><?= e((string) $invoice['booking_reference']) ?></p>
        </div>
    </div>

    <table class="w-full text-left text-body-sm">
        <thead>
            <tr class="border-b border-outline-variant">
                <th class="label-caps py-2 text-outline">Description</th>
                <th class="label-caps py-2 text-right text-outline">Qty</th>
                <th class="label-caps py-2 text-right text-outline">Unit</th>
                <th class="label-caps py-2 text-right text-outline">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr class="border-b border-outline-variant/60">
                    <td class="py-2">
                        <span class="text-[10px] uppercase text-outline"><?= e($billingService->labelForItemType((string) $item['item_type'])) ?></span>
                        <p><?= e((string) $item['description']) ?></p>
                    </td>
                    <td class="data-mono py-2 text-right"><?= e((string) $item['quantity']) ?></td>
                    <td class="data-mono py-2 text-right"><?= e(format_money($item['unit_price'])) ?></td>
                    <td class="data-mono py-2 text-right"><?= e(format_money($item['line_total'])) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="ml-auto w-full max-w-xs space-y-1 text-body-sm">
        <div class="flex justify-between"><span>Subtotal</span><span class="data-mono"><?= e(format_money($invoice['subtotal'])) ?></span></div>
        <div class="flex justify-between"><span>Discount</span><span class="data-mono">−<?= e(format_money($invoice['discount_amount'])) ?></span></div>
        <div class="flex justify-between"><span>Tax</span><span class="data-mono"><?= e(format_money($invoice['tax_amount'])) ?></span></div>
        <div class="flex justify-between border-t border-outline-variant pt-2 text-title-sm">
            <span>Total</span><span class="data-mono"><?= e(format_money($invoice['total_amount'])) ?></span>
        </div>
        <div class="flex justify-between"><span>Amount paid</span><span class="data-mono"><?= e(format_money($invoice['amount_paid'])) ?></span></div>
        <div class="flex justify-between font-bold text-primary">
            <span>Balance due</span><span class="data-mono"><?= e(format_money($invoice['balance_due'])) ?></span>
        </div>
    </div>

    <?php if (!empty($payments)): ?>
        <div class="border-t border-outline-variant pt-4">
            <p class="label-caps mb-2 text-outline">Payments received</p>
            <table class="w-full text-left text-body-sm">
                <thead>
                    <tr class="border-b border-outline-variant">
                        <th class="label-caps py-1 text-outline">Date</th>
                        <th class="label-caps py-1 text-outline">Method</th>
                        <th class="label-caps py-1 text-outline">Reference</th>
                        <th class="label-caps py-1 text-right text-outline">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $payment): ?>
                        <tr class="border-b border-outline-variant/50">
                            <td class="data-mono py-1"><?= e(format_datetime((string) $payment['paid_at'])) ?></td>
                            <td class="py-1"><?= e($paymentService->labelForMethod((string) $payment['method'])) ?></td>
                            <td class="data-mono py-1"><?= e((string) ($payment['reference_number'] ?: '—')) ?></td>
                            <td class="data-mono py-1 text-right"><?= e(format_money($payment['amount'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
