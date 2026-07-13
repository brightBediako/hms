<?php
/** @var array<string, string> $defaults */
/** @var array<string, string> $errors */
/** @var array<string, mixed> $old */

$value = static function (string $key) use ($old, $defaults): string {
    if (array_key_exists($key, $old) && $old[$key] !== null) {
        return (string) $old[$key];
    }

    return (string) ($defaults[$key] ?? '');
};
?>
<div class="mx-auto max-w-xl space-y-stack-gap">
    <div>
        <p class="text-body-sm text-on-surface-variant">
            <a href="<?= e(url('/settings')) ?>" class="hover:text-primary">Settings</a> / Hotel
        </p>
        <h1 class="text-headline-md text-on-surface">Hotel settings</h1>
        <p class="text-body-sm text-on-surface-variant">
            These values drive branding, money formatting, and default invoice tax.
        </p>
    </div>

    <form method="post" action="<?= e(url('/settings/hotel')) ?>" class="surface-card space-y-4 p-6">
        <?= \App\Core\CSRF::field() ?>

        <div>
            <label class="label-caps mb-2 block text-outline" for="hotel_name">Hotel name</label>
            <input id="hotel_name" name="hotel_name" class="input-field" required maxlength="100"
                   value="<?= e($value('hotel_name')) ?>">
            <?php if (!empty($errors['hotel_name'])): ?>
                <p class="mt-1 text-body-sm text-error"><?= e($errors['hotel_name']) ?></p>
            <?php endif; ?>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label-caps mb-2 block text-outline" for="currency">Currency</label>
                <input id="currency" name="currency" class="input-field data-mono uppercase" required maxlength="3"
                       value="<?= e($value('currency')) ?>" placeholder="GHS">
                <?php if (!empty($errors['currency'])): ?>
                    <p class="mt-1 text-body-sm text-error"><?= e($errors['currency']) ?></p>
                <?php endif; ?>
            </div>
            <div>
                <label class="label-caps mb-2 block text-outline" for="tax_rate_percent">Default tax (%)</label>
                <input id="tax_rate_percent" name="tax_rate_percent" type="number" step="0.01" min="0" max="100"
                       class="input-field data-mono" required value="<?= e($value('tax_rate_percent')) ?>">
                <?php if (!empty($errors['tax_rate_percent'])): ?>
                    <p class="mt-1 text-body-sm text-error"><?= e($errors['tax_rate_percent']) ?></p>
                <?php endif; ?>
                <p class="mt-1 text-[11px] text-on-surface-variant">Applied when generating new invoices with tax.</p>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label-caps mb-2 block text-outline" for="check_in_time">Check-in time</label>
                <input id="check_in_time" name="check_in_time" type="time" class="input-field" required
                       value="<?= e($value('check_in_time')) ?>">
                <?php if (!empty($errors['check_in_time'])): ?>
                    <p class="mt-1 text-body-sm text-error"><?= e($errors['check_in_time']) ?></p>
                <?php endif; ?>
            </div>
            <div>
                <label class="label-caps mb-2 block text-outline" for="check_out_time">Check-out time</label>
                <input id="check_out_time" name="check_out_time" type="time" class="input-field" required
                       value="<?= e($value('check_out_time')) ?>">
                <?php if (!empty($errors['check_out_time'])): ?>
                    <p class="mt-1 text-body-sm text-error"><?= e($errors['check_out_time']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="btn-action">Save settings</button>
            <a href="<?= e(url('/settings')) ?>" class="btn-outline">Cancel</a>
        </div>
    </form>
</div>
