<?php
/** @var array<string, mixed> $reservation */
/** @var bool $canEdit */
/** @var bool $canCancel */
/** @var \App\Services\ReservationService $reservationService */

$chip = $reservationService->chipClasses((string) $reservation['status']);
?>
<div class="space-y-stack-gap">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-body-sm text-on-surface-variant">
                <a href="<?= e(url('/reservations')) ?>" class="hover:text-primary">Reservations</a> / Detail
            </p>
            <h1 class="data-mono text-headline-md text-on-surface"><?= e((string) $reservation['booking_reference']) ?></h1>
            <div class="mt-2 flex flex-wrap items-center gap-2">
                <span class="inline-block rounded-sm px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider <?= e($chip['bg']) ?> <?= e($chip['text']) ?>">
                    <?= e($reservationService->labelForStatus((string) $reservation['status'])) ?>
                </span>
                <span class="text-body-sm text-on-surface-variant">
                    <?= e($reservationService->labelForSource((string) $reservation['source'])) ?>
                </span>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            <?php if ($canEdit): ?>
                <a href="<?= e(url('/reservations/' . $reservation['id'] . '/edit')) ?>" class="btn-outline">Edit</a>
            <?php endif; ?>
            <a href="<?= e(url('/reservations/calendar?from=' . urlencode((string) $reservation['check_in_date']))) ?>" class="btn-ghost">
                View on calendar
            </a>
        </div>
    </div>

    <div class="grid gap-stack-gap lg:grid-cols-2">
        <section class="surface-card space-y-4 p-6">
            <h2 class="text-title-sm text-on-surface">Stay</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <p class="label-caps mb-1 text-outline">Guest</p>
                    <a class="text-body-sm font-semibold text-primary-container" href="<?= e(url('/guests/' . $reservation['guest_id'])) ?>">
                        <?= e((string) $reservation['guest_name']) ?>
                    </a>
                    <p class="data-mono text-[11px] text-on-surface-variant"><?= e((string) ($reservation['guest_phone'] ?: '')) ?></p>
                </div>
                <div>
                    <p class="label-caps mb-1 text-outline">Room</p>
                    <p class="text-body-sm">
                        <span class="data-mono">#<?= e((string) $reservation['room_number']) ?></span>
                        · <?= e((string) $reservation['room_type_name']) ?>
                    </p>
                    <p class="text-[11px] text-on-surface-variant">Room status: <?= e((string) $reservation['room_status']) ?></p>
                </div>
                <div>
                    <p class="label-caps mb-1 text-outline">Check-in</p>
                    <p class="data-mono text-body-sm"><?= e(format_date((string) $reservation['check_in_date'])) ?></p>
                </div>
                <div>
                    <p class="label-caps mb-1 text-outline">Check-out</p>
                    <p class="data-mono text-body-sm"><?= e(format_date((string) $reservation['check_out_date'])) ?></p>
                </div>
                <div>
                    <p class="label-caps mb-1 text-outline">Occupancy</p>
                    <p class="text-body-sm">
                        <?= (int) $reservation['adults'] ?> adults
                        · <?= (int) $reservation['children'] ?> children
                    </p>
                </div>
                <div>
                    <p class="label-caps mb-1 text-outline">Agreed rate</p>
                    <p class="data-mono text-body-sm"><?= e(format_money($reservation['agreed_rate'])) ?>/night</p>
                </div>
            </div>
            <?php if (!empty($reservation['notes'])): ?>
                <div>
                    <p class="label-caps mb-1 text-outline">Notes</p>
                    <p class="text-body-sm whitespace-pre-wrap"><?= e((string) $reservation['notes']) ?></p>
                </div>
            <?php endif; ?>
            <?php if (!empty($reservation['cancellation_reason'])): ?>
                <div>
                    <p class="label-caps mb-1 text-outline">Cancellation reason</p>
                    <p class="text-body-sm"><?= e((string) $reservation['cancellation_reason']) ?></p>
                </div>
            <?php endif; ?>
            <?php if (!empty($reservation['booked_by_name'])): ?>
                <p class="text-[11px] text-on-surface-variant">
                    Booked by <?= e((string) $reservation['booked_by_name']) ?>
                    · <?= e(format_datetime((string) $reservation['created_at'])) ?>
                </p>
            <?php endif; ?>
        </section>

        <?php if ($canCancel): ?>
            <section class="surface-card space-y-4 p-6">
                <h2 class="text-title-sm text-on-surface">Cancel booking</h2>
                <p class="text-body-sm text-on-surface-variant">
                    Cancelling frees the room for other stays when no other active booking remains.
                </p>
                <form method="post" action="<?= e(url('/reservations/' . $reservation['id'] . '/cancel')) ?>"
                      class="space-y-3" onsubmit="return confirm('Cancel this reservation?');">
                    <?= \App\Core\CSRF::field() ?>
                    <div>
                        <label class="label-caps mb-1 block text-outline" for="cancellation_reason">Reason</label>
                        <input id="cancellation_reason" name="cancellation_reason" class="input-field"
                               placeholder="Optional">
                    </div>
                    <button type="submit" class="btn-outline border-error text-error">Cancel reservation</button>
                </form>
            </section>
        <?php else: ?>
            <section class="surface-card p-6 text-body-sm text-on-surface-variant">
                Check-in and check-out actions will live under Front Desk (feature 09).
            </section>
        <?php endif; ?>
    </div>
</div>
