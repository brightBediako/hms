<?php
/** @var string $date */
/** @var list<array<string, mixed>> $arrivals */
/** @var list<array<string, mixed>> $departures */
/** @var list<array<string, mixed>> $inHouse */
/** @var array<string, mixed>|null $selected */
/** @var list<array<string, mixed>> $transfers */
/** @var list<array<string, mixed>> $candidateRooms */
/** @var bool $canCheckIn */
/** @var bool $canCheckOut */
/** @var bool $canTransfer */
/** @var \App\Services\ReservationService $reservationService */

$selectedId = $selected ? (int) $selected['id'] : 0;
$selectUrl = static function (int $id) use ($date): string {
    return url('/frontdesk?date=' . urlencode($date) . '&selected=' . $id);
};
?>
<div class="space-y-stack-gap">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-headline-md text-primary">Front Desk Operations</h1>
            <p class="text-body-sm text-on-surface-variant">
                Manage arriving guests, room assignments, and stay changes.
            </p>
        </div>
        <form method="get" action="<?= e(url('/frontdesk')) ?>" class="flex items-end gap-2">
            <?php if ($selectedId > 0): ?>
                <input type="hidden" name="selected" value="<?= $selectedId ?>">
            <?php endif; ?>
            <div>
                <label class="label-caps mb-1 block text-outline" for="date">Business date</label>
                <input id="date" type="date" name="date" class="input-field" value="<?= e($date) ?>"
                       onchange="this.form.submit()">
            </div>
        </form>
    </div>

    <div class="grid grid-cols-1 gap-stack-gap xl:grid-cols-12">
        <!-- Arrivals -->
        <section class="surface-card flex flex-col overflow-hidden xl:col-span-4">
            <div class="flex items-center justify-between border-b border-outline-variant bg-surface-container-low px-4 py-2">
                <h2 class="label-caps text-on-surface-variant">Arrivals</h2>
                <span class="rounded bg-primary-container px-2 py-0.5 text-[10px] font-bold text-on-primary">
                    <?= count($arrivals) ?>
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-surface-container-low/50">
                        <tr>
                            <th class="label-caps px-3 py-2 text-outline">Guest</th>
                            <th class="label-caps px-3 py-2 text-outline">Room</th>
                            <th class="label-caps px-3 py-2 text-outline"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($arrivals === []): ?>
                            <tr>
                                <td colspan="3" class="px-3 py-6 text-center text-body-sm text-on-surface-variant">
                                    No arrivals due for this date.
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($arrivals as $row): ?>
                            <?php $active = $selectedId === (int) $row['id']; ?>
                            <tr class="border-t border-outline-variant <?= $active ? 'bg-primary/[0.05]' : 'hover:bg-primary/[0.02]' ?>">
                                <td class="px-3 py-2">
                                    <a href="<?= e($selectUrl((int) $row['id'])) ?>" class="text-body-sm font-semibold text-on-surface hover:text-primary">
                                        <?= e((string) $row['guest_name']) ?>
                                    </a>
                                    <p class="data-mono text-[10px] text-on-surface-variant"><?= e((string) $row['booking_reference']) ?></p>
                                    <?php if ($row['check_in_date'] < $date): ?>
                                        <p class="text-[10px] font-bold text-error">Overdue arrival</p>
                                    <?php endif; ?>
                                </td>
                                <td class="px-3 py-2 data-mono text-body-sm">#<?= e((string) $row['room_number']) ?></td>
                                <td class="px-3 py-2 text-right">
                                    <a class="btn-ghost text-[11px]" href="<?= e($selectUrl((int) $row['id'])) ?>">Open</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Departures -->
        <section class="surface-card flex flex-col overflow-hidden xl:col-span-4">
            <div class="flex items-center justify-between border-b border-outline-variant bg-surface-container-low px-4 py-2">
                <h2 class="label-caps text-on-surface-variant">Departures</h2>
                <span class="rounded bg-tertiary-container px-2 py-0.5 text-[10px] font-bold text-on-tertiary-container">
                    <?= count($departures) ?>
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-surface-container-low/50">
                        <tr>
                            <th class="label-caps px-3 py-2 text-outline">Guest</th>
                            <th class="label-caps px-3 py-2 text-outline">Room</th>
                            <th class="label-caps px-3 py-2 text-outline"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($departures === []): ?>
                            <tr>
                                <td colspan="3" class="px-3 py-6 text-center text-body-sm text-on-surface-variant">
                                    No departures due for this date.
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($departures as $row): ?>
                            <?php $active = $selectedId === (int) $row['id']; ?>
                            <tr class="border-t border-outline-variant <?= $active ? 'bg-primary/[0.05]' : 'hover:bg-primary/[0.02]' ?>">
                                <td class="px-3 py-2">
                                    <a href="<?= e($selectUrl((int) $row['id'])) ?>" class="text-body-sm font-semibold text-on-surface hover:text-primary">
                                        <?= e((string) $row['guest_name']) ?>
                                    </a>
                                    <p class="data-mono text-[10px] text-on-surface-variant"><?= e((string) $row['booking_reference']) ?></p>
                                </td>
                                <td class="px-3 py-2 data-mono text-body-sm">#<?= e((string) $row['room_number']) ?></td>
                                <td class="px-3 py-2 text-right">
                                    <a class="btn-ghost text-[11px]" href="<?= e($selectUrl((int) $row['id'])) ?>">Open</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Action panel -->
        <aside class="surface-card flex flex-col overflow-hidden xl:col-span-4">
            <div class="border-b border-outline-variant bg-surface-container-low px-4 py-2">
                <h2 class="label-caps text-on-surface-variant">Stay actions</h2>
            </div>
            <div class="custom-scrollbar flex-1 space-y-4 overflow-y-auto p-4">
                <?php if ($selected === null): ?>
                    <p class="text-body-sm text-on-surface-variant">Select an arrival, departure, or in-house guest.</p>
                <?php else: ?>
                    <?php $chip = $reservationService->chipClasses((string) $selected['status']); ?>
                    <div class="rounded border border-outline-variant bg-primary/[0.04] p-3">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <h3 class="text-title-sm text-primary"><?= e((string) $selected['guest_name']) ?></h3>
                                <p class="data-mono text-[11px] text-on-surface-variant"><?= e((string) $selected['booking_reference']) ?></p>
                            </div>
                            <span class="inline-block rounded-sm px-2 py-0.5 text-[10px] font-bold uppercase <?= e($chip['bg']) ?> <?= e($chip['text']) ?>">
                                <?= e($reservationService->labelForStatus((string) $selected['status'])) ?>
                            </span>
                        </div>
                        <p class="mt-2 text-body-sm text-on-surface-variant">
                            <span class="data-mono">#<?= e((string) $selected['room_number']) ?></span>
                            · <?= e((string) $selected['room_type_name']) ?>
                        </p>
                        <p class="data-mono text-[11px] text-on-surface-variant">
                            <?= e(format_date((string) $selected['check_in_date'])) ?>
                            →
                            <?= e(format_date((string) $selected['check_out_date'])) ?>
                        </p>
                        <a class="mt-2 inline-block text-[11px] font-bold text-primary-container" href="<?= e(url('/reservations/' . $selected['id'])) ?>">
                            Full reservation
                        </a>
                    </div>

                    <?php if ($selected['status'] === 'booked' && $canCheckIn): ?>
                        <form method="post" action="<?= e(url('/frontdesk/' . $selected['id'] . '/assign')) ?>" class="space-y-2">
                            <?= \App\Core\CSRF::field() ?>
                            <input type="hidden" name="date" value="<?= e($date) ?>">
                            <label class="label-caps block text-outline" for="assign_room_id">Assign room</label>
                            <div class="flex gap-2">
                                <select id="assign_room_id" name="room_id" class="input-field" required>
                                    <?php foreach ($candidateRooms as $room): ?>
                                        <option value="<?= (int) $room['id'] ?>" <?= (int) $selected['room_id'] === (int) $room['id'] ? 'selected' : '' ?>>
                                            #<?= e((string) $room['room_number']) ?> · <?= e((string) $room['room_type_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn-outline shrink-0">Assign</button>
                            </div>
                        </form>

                        <form method="post" action="<?= e(url('/frontdesk/' . $selected['id'] . '/check-in')) ?>" class="space-y-2">
                            <?= \App\Core\CSRF::field() ?>
                            <input type="hidden" name="date" value="<?= e($date) ?>">
                            <label class="label-caps block text-outline" for="checkin_room_id">Confirm check-in</label>
                            <select id="checkin_room_id" name="room_id" class="input-field" required>
                                <?php foreach ($candidateRooms as $room): ?>
                                    <option value="<?= (int) $room['id'] ?>" <?= (int) $selected['room_id'] === (int) $room['id'] ? 'selected' : '' ?>>
                                        #<?= e((string) $room['room_number']) ?> · <?= e((string) $room['room_type_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn-action w-full">
                                <span class="material-symbols-outlined text-[18px]">login</span>
                                Confirm Check-In
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if ($selected['status'] === 'checked_in'): ?>
                        <?php if ($canCheckOut): ?>
                            <form method="post" action="<?= e(url('/frontdesk/' . $selected['id'] . '/check-out')) ?>"
                                  onsubmit="return confirm('Check out this guest? Room will go to cleaning.');">
                                <?= \App\Core\CSRF::field() ?>
                                <input type="hidden" name="date" value="<?= e($date) ?>">
                                <button type="submit" class="btn-primary w-full">
                                    <span class="material-symbols-outlined text-[18px]">logout</span>
                                    Check Out
                                </button>
                            </form>
                        <?php endif; ?>

                        <?php if ($canTransfer): ?>
                            <form method="post" action="<?= e(url('/frontdesk/' . $selected['id'] . '/transfer')) ?>" class="space-y-2 border-t border-outline-variant pt-4">
                                <?= \App\Core\CSRF::field() ?>
                                <input type="hidden" name="date" value="<?= e($date) ?>">
                                <p class="label-caps text-outline">Room transfer</p>
                                <select name="to_room_id" class="input-field" required>
                                    <option value="">Target room…</option>
                                    <?php foreach ($candidateRooms as $room): ?>
                                        <?php if ((int) $room['id'] === (int) $selected['room_id']) {
                                            continue;
                                        } ?>
                                        <option value="<?= (int) $room['id'] ?>">
                                            #<?= e((string) $room['room_number']) ?> · <?= e((string) $room['room_type_name']) ?>
                                            (<?= e((string) $room['status']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input name="reason" class="input-field" placeholder="Reason (optional)">
                                <button type="submit" class="btn-outline w-full">
                                    <span class="material-symbols-outlined text-[18px]">swap_horiz</span>
                                    Transfer
                                </button>
                            </form>
                        <?php endif; ?>

                        <?php if ($canCheckIn || $canTransfer): ?>
                            <form method="post" action="<?= e(url('/frontdesk/' . $selected['id'] . '/extend')) ?>" class="space-y-2 border-t border-outline-variant pt-4">
                                <?= \App\Core\CSRF::field() ?>
                                <input type="hidden" name="date" value="<?= e($date) ?>">
                                <p class="label-caps text-outline">Stay extension</p>
                                <input type="date" name="check_out_date" class="input-field" required
                                       min="<?= e((string) $selected['check_out_date']) ?>"
                                       value="<?= e((new DateTimeImmutable((string) $selected['check_out_date']))->modify('+1 day')->format('Y-m-d')) ?>">
                                <button type="submit" class="btn-outline w-full">
                                    <span class="material-symbols-outlined text-[18px]">event</span>
                                    Extend stay
                                </button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($transfers !== []): ?>
                        <div class="border-t border-outline-variant pt-4">
                            <p class="label-caps mb-2 text-outline">Transfer history</p>
                            <ul class="space-y-2">
                                <?php foreach ($transfers as $t): ?>
                                    <li class="rounded border border-outline-variant px-2 py-1.5 text-[11px]">
                                        <span class="data-mono">#<?= e((string) $t['from_room_number']) ?></span>
                                        →
                                        <span class="data-mono">#<?= e((string) $t['to_room_number']) ?></span>
                                        <p class="text-on-surface-variant">
                                            <?= e(format_datetime((string) $t['transferred_at'])) ?>
                                            <?php if (!empty($t['reason'])): ?>
                                                · <?= e((string) $t['reason']) ?>
                                            <?php endif; ?>
                                        </p>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </aside>
    </div>

    <!-- In-house -->
    <section class="surface-card overflow-hidden">
        <div class="flex items-center justify-between border-b border-outline-variant bg-surface-container-low px-4 py-2">
            <h2 class="label-caps text-on-surface-variant">In-house guests</h2>
            <span class="data-mono text-[11px] text-on-surface-variant"><?= count($inHouse) ?> occupied</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-surface-container-low">
                    <tr>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Guest</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Reference</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Room</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Dates</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($inHouse === []): ?>
                        <tr>
                            <td colspan="5" class="px-cell-x py-8 text-center text-body-sm text-on-surface-variant">
                                No guests currently in-house. Check in an arrival to begin.
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($inHouse as $row): ?>
                        <tr class="border-t border-outline-variant hover:bg-primary/[0.02]">
                            <td class="px-cell-x py-cell-y text-body-sm font-semibold"><?= e((string) $row['guest_name']) ?></td>
                            <td class="px-cell-x py-cell-y data-mono text-body-sm"><?= e((string) $row['booking_reference']) ?></td>
                            <td class="px-cell-x py-cell-y data-mono text-body-sm">#<?= e((string) $row['room_number']) ?></td>
                            <td class="px-cell-x py-cell-y data-mono text-body-sm text-on-surface-variant">
                                <?= e(format_date((string) $row['check_in_date'])) ?>
                                →
                                <?= e(format_date((string) $row['check_out_date'])) ?>
                            </td>
                            <td class="px-cell-x py-cell-y text-right">
                                <a class="btn-ghost" href="<?= e($selectUrl((int) $row['id'])) ?>">Manage</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
