<?php
/** @var array<string, mixed>|null $invoice */
/** @var list<array<string, mixed>> $payableInvoices */
/** @var array<string, string> $errors */
/** @var array<string, mixed> $old */
/** @var \App\Services\PaymentService $paymentService */

$value = static function (string $key, mixed $default = '') use ($old, $invoice): string {
    if (array_key_exists($key, $old) && $old[$key] !== null) {
        return (string) $old[$key];
    }
    if ($key === 'invoice_id' && $invoice !== null) {
        return (string) $invoice['id'];
    }
    if ($key === 'amount' && $invoice !== null) {
        return (string) $invoice['balance_due'];
    }

    return (string) $default;
};
?>
<div class="mx-auto max-w-xl space-y-stack-gap">
    <div>
        <p class="text-body-sm text-on-surface-variant">
            <a href="<?= e(url('/payments')) ?>" class="hover:text-primary">Payments</a> / Record
        </p>
        <h1 class="text-headline-md text-on-surface">Record payment</h1>
    </div>

    <?php if ($payableInvoices === [] && $invoice === null): ?>
        <div class="surface-card p-6 text-body-sm text-on-surface-variant">
            No issued invoices with a balance due.
            <a class="font-semibold text-primary-container" href="<?= e(url('/billing')) ?>">Open billing</a>
        </div>
    <?php else: ?>
        <form method="post" action="<?= e(url('/payments')) ?>" class="surface-card space-y-4 p-6">
            <?= \App\Core\CSRF::field() ?>

            <div>
                <label class="label-caps mb-2 block text-outline" for="invoice_id">Invoice</label>
                <select id="invoice_id" name="invoice_id" class="input-field" required>
                    <option value="">Select invoice…</option>
                    <?php
                    $options = $payableInvoices;
                    if ($invoice !== null) {
                        $ids = array_map(static fn (array $r): int => (int) $r['id'], $options);
                        if (!in_array((int) $invoice['id'], $ids, true)) {
                            array_unshift($options, $invoice);
                        }
                    }
                    foreach ($options as $row):
                    ?>
                        <option value="<?= (int) $row['id'] ?>" <?= $value('invoice_id') === (string) $row['id'] ? 'selected' : '' ?>>
                            <?= e((string) $row['invoice_number']) ?>
                            · <?= e((string) ($row['guest_name'] ?? '')) ?>
                            · due <?= e(format_money($row['balance_due'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($errors['invoice_id'])): ?>
                    <p class="mt-1 text-body-sm text-error"><?= e($errors['invoice_id']) ?></p>
                <?php endif; ?>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label-caps mb-2 block text-outline" for="method">Method</label>
                    <select id="method" name="method" class="input-field" required>
                        <?php foreach (\App\Models\Payment::METHODS as $method): ?>
                            <option value="<?= e($method) ?>" <?= $value('method', 'cash') === $method ? 'selected' : '' ?>>
                                <?= e($paymentService->labelForMethod($method)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="label-caps mb-2 block text-outline" for="amount">Amount</label>
                    <input id="amount" name="amount" type="number" step="0.01" min="0.01" class="input-field data-mono" required
                           value="<?= e($value('amount')) ?>">
                    <?php if (!empty($errors['amount'])): ?>
                        <p class="mt-1 text-body-sm text-error"><?= e($errors['amount']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div>
                <label class="label-caps mb-2 block text-outline" for="reference_number">Reference number</label>
                <input id="reference_number" name="reference_number" class="input-field data-mono" maxlength="100"
                       placeholder="MoMo txn / card auth / receipt #"
                       value="<?= e($value('reference_number')) ?>">
            </div>

            <div>
                <label class="label-caps mb-2 block text-outline" for="notes">Notes</label>
                <textarea id="notes" name="notes" rows="2" class="input-field resize-none"><?= e($value('notes')) ?></textarea>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="btn-action">Save payment</button>
                <a href="<?= e(url('/payments')) ?>" class="btn-outline">Cancel</a>
            </div>
        </form>
    <?php endif; ?>
</div>
