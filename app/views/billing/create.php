<?php
/** @var list<array<string, mixed>> $reservations */
/** @var int $selectedReservationId */
/** @var float $taxRatePercent */
/** @var string $taxLinesLabel */
/** @var array<string, string> $errors */
?>
<div class="mx-auto max-w-xl space-y-stack-gap">
    <div>
        <p class="text-body-sm text-on-surface-variant">
            <a href="<?= e(url('/billing')) ?>" class="hover:text-primary">Billing</a> / Generate
        </p>
        <h1 class="text-headline-md text-on-surface">Generate invoice</h1>
        <p class="text-body-sm text-on-surface-variant">
            Creates a draft folio from an in-house or checked-out reservation (room nights + optional tax).
        </p>
    </div>

    <?php if ($reservations === []): ?>
        <div class="surface-card p-6 text-body-sm text-on-surface-variant">
            No billable stays without an active invoice.
            Check a guest in at <a class="font-semibold text-primary-container" href="<?= e(url('/frontdesk')) ?>">Front Desk</a> first.
        </div>
    <?php else: ?>
        <form method="post" action="<?= e(url('/billing')) ?>" class="surface-card space-y-4 p-6">
            <?= \App\Core\CSRF::field() ?>

            <div>
                <label class="label-caps mb-2 block text-outline" for="reservation_id">Reservation</label>
                <select id="reservation_id" name="reservation_id" class="input-field" required>
                    <option value="">Select stay…</option>
                    <?php foreach ($reservations as $row): ?>
                        <option value="<?= (int) $row['id'] ?>" <?= $selectedReservationId === (int) $row['id'] ? 'selected' : '' ?>>
                            <?= e((string) $row['booking_reference']) ?>
                            · <?= e((string) $row['guest_name']) ?>
                            · #<?= e((string) $row['room_number']) ?>
                            · <?= e((string) $row['status']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($errors['reservation_id'])): ?>
                    <p class="mt-1 text-body-sm text-error"><?= e($errors['reservation_id']) ?></p>
                <?php endif; ?>
            </div>

            <label class="flex items-center gap-2 text-body-sm">
                <input type="checkbox" name="include_tax" value="1" checked
                       class="h-4 w-4 rounded border-outline-variant text-primary focus:ring-primary">
                Include taxes (<?= e((string) ($taxLinesLabel ?? ($taxRatePercent . '%'))) ?>)
            </label>

            <div class="flex gap-2">
                <button type="submit" class="btn-action">Create draft invoice</button>
                <a href="<?= e(url('/billing')) ?>" class="btn-outline">Cancel</a>
            </div>
        </form>
    <?php endif; ?>
</div>
