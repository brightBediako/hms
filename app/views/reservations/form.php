<?php
/** @var array<string, mixed>|null $reservation */
/** @var array<string, mixed>|null $selectedGuest */
/** @var list<array<string, mixed>> $availableRooms */
/** @var array<string, string> $errors */
/** @var array<string, mixed> $old */
/** @var list<array<string, mixed>> $conflicts */
/** @var \App\Services\ReservationService $reservationService */
/** @var \App\Services\GuestService $guestService */
/** @var string $guestSearchUrl */
/** @var bool $canCollectPayment */
/** @var \App\Services\PaymentService|null $paymentService */
/** @var float|null $taxRate */
/** @var string|null $taxLinesLabel */
/** @var string|null $currency */

$isEdit = $reservation !== null;
$action = $isEdit ? url('/reservations/' . $reservation['id']) : url('/reservations');
$value = static function (string $key, mixed $default = '') use ($old): string {
    if (array_key_exists($key, $old) && $old[$key] !== null) {
        return (string) $old[$key];
    }

    return (string) $default;
};

$guestMode = $value('guest_mode', $isEdit ? 'returning' : 'new');
$checkoutTime = \App\Services\ReservationService::STANDARD_CHECK_OUT_TIME;
?>
<div class="mx-auto max-w-3xl space-y-stack-gap">
    <div>
        <p class="text-body-sm text-on-surface-variant">
            <a href="<?= e(url('/reservations')) ?>" class="hover:text-primary">Reservations</a>
            / <?= $isEdit ? 'Edit' : 'New' ?>
        </p>
        <h1 class="text-headline-md text-on-surface"><?= e($title ?? 'Reservation') ?></h1>
    </div>

    <?php if (!empty($errors['_form'])): ?>
        <div class="rounded border border-error-container bg-error-container/40 px-4 py-3 text-body-sm text-on-error-container">
            <?= e($errors['_form']) ?>
        </div>
    <?php endif; ?>

    <?php if ($conflicts !== []): ?>
        <div class="rounded border border-outline-variant bg-surface-container-low px-4 py-3 text-body-sm">
            <p class="label-caps mb-2 text-outline">Conflicting bookings</p>
            <ul class="space-y-1">
                <?php foreach ($conflicts as $c): ?>
                    <li class="data-mono">
                        <?= e((string) $c['booking_reference']) ?>
                        · <?= e(format_date((string) $c['check_in_date'])) ?> → <?= e(format_date((string) $c['check_out_date'])) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= e($action) ?>" class="surface-card space-y-6 p-6" id="reservation-form"
          data-availability-url="<?= e(url('/reservations/availability')) ?>"
          data-guest-search-url="<?= e($guestSearchUrl) ?>"
          data-except-id="<?= $isEdit ? (int) $reservation['id'] : 0 ?>">
        <?= \App\Core\CSRF::field() ?>

        <fieldset class="space-y-4">
            <legend class="label-caps text-outline">Guest</legend>

            <div class="flex flex-wrap gap-2" role="group" aria-label="Guest mode">
                <label class="inline-flex cursor-pointer items-center gap-2 rounded border border-outline-variant px-3 py-2 text-body-sm <?= $guestMode === 'new' ? 'border-primary bg-primary-fixed text-on-primary-fixed' : 'bg-surface' ?>">
                    <input type="radio" name="guest_mode" value="new" class="sr-only" <?= $guestMode === 'new' ? 'checked' : '' ?> data-guest-mode>
                    New guest
                </label>
                <label class="inline-flex cursor-pointer items-center gap-2 rounded border border-outline-variant px-3 py-2 text-body-sm <?= $guestMode === 'returning' ? 'border-primary bg-primary-fixed text-on-primary-fixed' : 'bg-surface' ?>">
                    <input type="radio" name="guest_mode" value="returning" class="sr-only" <?= $guestMode === 'returning' ? 'checked' : '' ?> data-guest-mode>
                    Returning guest
                </label>
            </div>

            <div id="guest-new-panel" class="space-y-4 <?= $guestMode === 'new' ? '' : 'hidden' ?>">
                <div>
                    <label class="label-caps mb-2 block text-outline" for="guest_full_name">Full name</label>
                    <input id="guest_full_name" name="guest_full_name" class="input-field" maxlength="150"
                           value="<?= e($value('guest_full_name')) ?>" placeholder="Ama Mensah"
                           <?= $guestMode === 'new' ? 'required' : '' ?>>
                    <?php if (!empty($errors['guest_full_name'])): ?>
                        <p class="mt-1 text-body-sm text-error"><?= e($errors['guest_full_name']) ?></p>
                    <?php endif; ?>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label-caps mb-2 block text-outline" for="guest_phone">Phone</label>
                        <input id="guest_phone" name="guest_phone" class="input-field data-mono" maxlength="30"
                               value="<?= e($value('guest_phone')) ?>" placeholder="+233…">
                    </div>
                    <div>
                        <label class="label-caps mb-2 block text-outline" for="guest_email">Email</label>
                        <input id="guest_email" name="guest_email" type="email" class="input-field" maxlength="150"
                               value="<?= e($value('guest_email')) ?>" placeholder="guest@example.com">
                        <?php if (!empty($errors['guest_email'])): ?>
                            <p class="mt-1 text-body-sm text-error"><?= e($errors['guest_email']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label-caps mb-2 block text-outline" for="guest_id_type">ID type</label>
                        <select id="guest_id_type" name="guest_id_type" class="input-field">
                            <option value="">—</option>
                            <?php foreach (\App\Models\Guest::ID_TYPES as $type): ?>
                                <option value="<?= e($type) ?>" <?= $value('guest_id_type') === $type ? 'selected' : '' ?>>
                                    <?= e($guestService->labelForIdType($type)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="label-caps mb-2 block text-outline" for="guest_id_number">ID number</label>
                        <input id="guest_id_number" name="guest_id_number" class="input-field data-mono" maxlength="100"
                               value="<?= e($value('guest_id_number')) ?>">
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label-caps mb-2 block text-outline" for="guest_nationality">Nationality</label>
                        <input id="guest_nationality" name="guest_nationality" class="input-field" maxlength="80"
                               value="<?= e($value('guest_nationality')) ?>" placeholder="Ghanaian">
                    </div>
                    <div>
                        <label class="label-caps mb-2 block text-outline" for="guest_address">Address</label>
                        <input id="guest_address" name="guest_address" class="input-field" maxlength="255"
                               value="<?= e($value('guest_address')) ?>">
                    </div>
                </div>
                <div>
                    <label class="label-caps mb-2 block text-outline" for="guest_notes">Guest notes</label>
                    <textarea id="guest_notes" name="guest_notes" rows="2" class="input-field resize-none"
                              placeholder="Preferences, VIP flags…"><?= e($value('guest_notes')) ?></textarea>
                </div>
            </div>

            <div id="guest-returning-panel" class="space-y-3 <?= $guestMode === 'returning' ? '' : 'hidden' ?>">
                <div class="relative">
                    <label class="label-caps mb-2 block text-outline" for="guest_search">Search guest</label>
                    <input id="guest_search" type="search" class="input-field" autocomplete="off"
                           placeholder="Name, phone, or email…"
                           value="<?= e($selectedGuest['full_name'] ?? '') ?>">
                    <input type="hidden" id="guest_id" name="guest_id" value="<?= e($value('guest_id')) ?>"
                           <?= $guestMode === 'returning' ? 'required' : '' ?>>
                    <ul id="guest-search-results"
                        class="absolute z-10 mt-1 hidden max-h-56 w-full overflow-auto rounded border border-outline-variant bg-surface shadow-md"></ul>
                </div>
                <p id="guest-selected-label" class="text-body-sm text-on-surface-variant <?= $selectedGuest ? '' : 'hidden' ?>">
                    Selected:
                    <span class="font-semibold text-on-surface" data-guest-selected-name>
                        <?= e((string) ($selectedGuest['full_name'] ?? '')) ?>
                    </span>
                    <?php if (!empty($selectedGuest['phone'])): ?>
                        <span class="data-mono text-[11px]">· <?= e((string) $selectedGuest['phone']) ?></span>
                    <?php endif; ?>
                </p>
                <?php if (!empty($errors['guest_id'])): ?>
                    <p class="text-body-sm text-error"><?= e($errors['guest_id']) ?></p>
                <?php endif; ?>
            </div>
        </fieldset>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label-caps mb-2 block text-outline" for="check_in_date">Check-in date</label>
                <input id="check_in_date" name="check_in_date" type="date" class="input-field" required
                       value="<?= e($value('check_in_date')) ?>">
            </div>
            <div>
                <label class="label-caps mb-2 block text-outline" for="check_in_time_display">Check-in time</label>
                <?php if ($reservation): ?>
                    <input id="check_in_time_display" type="time" class="input-field data-mono bg-surface-container-low"
                           value="<?= e($value('check_in_time')) ?>" readonly tabindex="-1" aria-readonly="true">
                    <p class="mt-1 text-[11px] text-on-surface-variant">Recorded when this reservation was created.</p>
                <?php else: ?>
                    <input id="check_in_time_display" type="text" class="input-field bg-surface-container-low"
                           value="Set when you save" readonly tabindex="-1" aria-readonly="true">
                    <p class="mt-1 text-[11px] text-on-surface-variant">Uses the time this reservation is created.</p>
                <?php endif; ?>
            </div>
            <div>
                <label class="label-caps mb-2 block text-outline" for="check_out_date">Check-out date</label>
                <input id="check_out_date" name="check_out_date" type="date" class="input-field" required
                       value="<?= e($value('check_out_date')) ?>">
            </div>
            <div>
                <label class="label-caps mb-2 block text-outline" for="check_out_time_display">Check-out time</label>
                <input id="check_out_time_display" type="time" class="input-field data-mono bg-surface-container-low"
                       value="<?= e($checkoutTime) ?>" readonly tabindex="-1" aria-readonly="true">
                <input type="hidden" name="check_out_time" value="<?= e($checkoutTime) ?>">
                <p class="mt-1 text-[11px] text-on-surface-variant">Checkout is always 12:00 PM (hotel policy).</p>
            </div>
        </div>

        <div>
            <label class="label-caps mb-2 block text-outline" for="room_id">Room</label>
            <select id="room_id" name="room_id" class="input-field" required>
                <option value="">Select available room…</option>
                <?php foreach ($availableRooms as $room): ?>
                    <option value="<?= (int) $room['id'] ?>"
                            data-rate="<?= e((string) $room['base_rate']) ?>"
                            <?= $value('room_id') === (string) $room['id'] ? 'selected' : '' ?>>
                        #<?= e((string) $room['room_number']) ?>
                        · <?= e((string) $room['room_type_name']) ?>
                        · <?= e(format_money($room['base_rate'])) ?>/night
                        (<?= e((string) $room['status']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <p id="room-availability-hint" class="mt-1 text-[11px] text-on-surface-variant">
                Room list updates when dates change (availability check).
            </p>
            <?php if (!empty($errors['room_id'])): ?>
                <p class="mt-1 text-body-sm text-error"><?= e($errors['room_id']) ?></p>
            <?php endif; ?>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label class="label-caps mb-2 block text-outline" for="source">Source</label>
                <select id="source" name="source" class="input-field" required>
                    <?php foreach (\App\Models\Reservation::SOURCES as $source): ?>
                        <option value="<?= e($source) ?>" <?= $value('source', 'advance') === $source ? 'selected' : '' ?>>
                            <?= e($reservationService->labelForSource($source)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="label-caps mb-2 block text-outline" for="adults">Adults</label>
                <input id="adults" name="adults" type="number" min="1" class="input-field data-mono" required
                       value="<?= e($value('adults', '1')) ?>">
            </div>
            <div>
                <label class="label-caps mb-2 block text-outline" for="children">Children</label>
                <input id="children" name="children" type="number" min="0" class="input-field data-mono"
                       value="<?= e($value('children', '0')) ?>">
            </div>
        </div>

        <div>
            <label class="label-caps mb-2 block text-outline" for="agreed_rate">Agreed nightly rate</label>
            <input id="agreed_rate" name="agreed_rate" type="number" step="0.01" min="0" class="input-field data-mono" required
                   value="<?= e($value('agreed_rate', $availableRooms[0]['base_rate'] ?? '0')) ?>">
            <?php if (!empty($errors['agreed_rate'])): ?>
                <p class="mt-1 text-body-sm text-error"><?= e($errors['agreed_rate']) ?></p>
            <?php endif; ?>
        </div>

        <?php if (!empty($canCollectPayment)): ?>
            <fieldset class="space-y-4 rounded border border-outline-variant p-4"
                      id="payment-at-booking"
                      data-tax-rate="<?= e((string) ($taxRate ?? 0)) ?>"
                      data-currency="<?= e((string) ($currency ?? 'GHS')) ?>">
                <legend class="label-caps px-1 text-outline">Payment at booking (optional)</legend>
                <p class="text-body-sm text-on-surface-variant">
                    Collect a full or partial payment now. Leave amount blank or 0 to skip.
                    Creates an advance invoice automatically.
                </p>

                <div class="grid gap-3 sm:grid-cols-3">
                    <div class="surface-card bg-surface-container-low p-3">
                        <p class="label-caps text-outline">Nights</p>
                        <p class="data-mono text-title-sm" id="estimate-nights">—</p>
                    </div>
                    <div class="surface-card bg-surface-container-low p-3">
                        <p class="label-caps text-outline">Room subtotal</p>
                        <p class="data-mono text-title-sm" id="estimate-room">—</p>
                    </div>
                    <div class="surface-card bg-surface-container-low p-3">
                        <p class="label-caps text-outline">Estimated total</p>
                        <p class="data-mono text-title-sm font-semibold" id="estimate-total">—</p>
                    </div>
                </div>

                <label class="flex items-center gap-2 text-body-sm">
                    <input type="hidden" name="payment_include_tax" value="0">
                    <input type="checkbox" name="payment_include_tax" value="1" id="payment_include_tax"
                           class="h-4 w-4 rounded border-outline-variant text-primary focus:ring-primary"
                           <?= $value('payment_include_tax', '1') === '1' ? 'checked' : '' ?>>
                    Include tax in estimate (<?= e((string) ($taxLinesLabel ?? number_format(((float) ($taxRate ?? 0)) * 100, 2) . '%')) ?>)
                </label>

                <div class="flex flex-wrap gap-2">
                    <button type="button" class="btn-outline" id="pay-full-btn">Pay full amount</button>
                    <button type="button" class="btn-ghost" id="pay-half-btn">Pay 50%</button>
                    <button type="button" class="btn-ghost" id="pay-clear-btn">No payment</button>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label-caps mb-2 block text-outline" for="payment_method">Method</label>
                        <select id="payment_method" name="payment_method" class="input-field">
                            <?php foreach (\App\Models\Payment::METHODS as $method): ?>
                                <option value="<?= e($method) ?>" <?= $value('payment_method', 'cash') === $method ? 'selected' : '' ?>>
                                    <?= e($paymentService->labelForMethod($method)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($errors['payment_method'])): ?>
                            <p class="mt-1 text-body-sm text-error"><?= e($errors['payment_method']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label class="label-caps mb-2 block text-outline" for="payment_amount">Amount</label>
                        <input id="payment_amount" name="payment_amount" type="number" step="0.01" min="0"
                               class="input-field data-mono"
                               value="<?= e($value('payment_amount')) ?>" placeholder="0.00">
                        <?php if (!empty($errors['payment_amount'])): ?>
                            <p class="mt-1 text-body-sm text-error"><?= e($errors['payment_amount']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div>
                    <label class="label-caps mb-2 block text-outline" for="payment_reference">Payment reference</label>
                    <input id="payment_reference" name="payment_reference" class="input-field data-mono" maxlength="100"
                           placeholder="MoMo txn / card auth / receipt #"
                           value="<?= e($value('payment_reference')) ?>">
                </div>

                <div>
                    <label class="label-caps mb-2 block text-outline" for="payment_notes">Payment notes</label>
                    <input id="payment_notes" name="payment_notes" class="input-field" maxlength="255"
                           value="<?= e($value('payment_notes')) ?>">
                </div>
            </fieldset>
        <?php endif; ?>

        <div>
            <label class="label-caps mb-2 block text-outline" for="notes">Reservation notes</label>
            <textarea id="notes" name="notes" rows="3" class="input-field resize-none"><?= e($value('notes')) ?></textarea>
        </div>

        <div class="flex flex-wrap gap-2">
            <button type="submit" class="btn-action"><?= $isEdit ? 'Save changes' : 'Create reservation' ?></button>
            <a href="<?= e($isEdit ? url('/reservations/' . $reservation['id']) : url('/reservations')) ?>" class="btn-outline">Cancel</a>
        </div>
    </form>
</div>
<script src="<?= e(asset('js/reservations.js')) ?>" defer></script>
