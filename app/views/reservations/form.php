<?php
/** @var array<string, mixed>|null $reservation */
/** @var list<array<string, mixed>> $guests */
/** @var list<array<string, mixed>> $availableRooms */
/** @var array<string, string> $errors */
/** @var array<string, mixed> $old */
/** @var list<array<string, mixed>> $conflicts */
/** @var \App\Services\ReservationService $reservationService */

$isEdit = $reservation !== null;
$action = $isEdit ? url('/reservations/' . $reservation['id']) : url('/reservations');
$value = static function (string $key, mixed $default = '') use ($old): string {
    if (array_key_exists($key, $old) && $old[$key] !== null) {
        return (string) $old[$key];
    }

    return (string) $default;
};
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
          data-except-id="<?= $isEdit ? (int) $reservation['id'] : 0 ?>">
        <?= \App\Core\CSRF::field() ?>

        <div>
            <label class="label-caps mb-2 block text-outline" for="guest_id">Guest</label>
            <select id="guest_id" name="guest_id" class="input-field" required>
                <option value="">Select guest…</option>
                <?php foreach ($guests as $guest): ?>
                    <option value="<?= (int) $guest['id'] ?>" <?= $value('guest_id') === (string) $guest['id'] ? 'selected' : '' ?>>
                        <?= e((string) $guest['full_name']) ?>
                        <?php if (!empty($guest['phone'])): ?>
                            · <?= e((string) $guest['phone']) ?>
                        <?php endif; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($guests === []): ?>
                <p class="mt-1 text-body-sm text-on-surface-variant">
                    <a class="font-semibold text-primary-container" href="<?= e(url('/guests/create')) ?>">Create a guest</a> first.
                </p>
            <?php endif; ?>
            <?php if (!empty($errors['guest_id'])): ?>
                <p class="mt-1 text-body-sm text-error"><?= e($errors['guest_id']) ?></p>
            <?php endif; ?>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label-caps mb-2 block text-outline" for="check_in_date">Check-in</label>
                <input id="check_in_date" name="check_in_date" type="date" class="input-field" required
                       value="<?= e($value('check_in_date')) ?>">
            </div>
            <div>
                <label class="label-caps mb-2 block text-outline" for="check_out_date">Check-out</label>
                <input id="check_out_date" name="check_out_date" type="date" class="input-field" required
                       value="<?= e($value('check_out_date')) ?>">
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

        <div>
            <label class="label-caps mb-2 block text-outline" for="notes">Notes</label>
            <textarea id="notes" name="notes" rows="3" class="input-field resize-none"><?= e($value('notes')) ?></textarea>
        </div>

        <div class="flex flex-wrap gap-2">
            <button type="submit" class="btn-action"><?= $isEdit ? 'Save changes' : 'Create reservation' ?></button>
            <a href="<?= e($isEdit ? url('/reservations/' . $reservation['id']) : url('/reservations')) ?>" class="btn-outline">Cancel</a>
        </div>
    </form>
</div>
<script src="<?= e(asset('js/reservations.js')) ?>" defer></script>
