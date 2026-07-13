<?php
/** @var list<array<string, mixed>> $reservations */
/** @var array{status:?string,q:?string,from:?string,to:?string} $filters */
/** @var bool $canCreate */
/** @var \App\Services\ReservationService $reservationService */
?>
<div class="space-y-stack-gap">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-headline-md text-on-surface">Reservations</h1>
            <p class="text-body-sm text-on-surface-variant">
                Walk-in and advance bookings with conflict-safe room assignment.
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="<?= e(url('/reservations/calendar')) ?>" class="btn-outline">
                <span class="material-symbols-outlined text-[18px]">calendar_view_week</span>
                Calendar
            </a>
            <?php if ($canCreate): ?>
                <a href="<?= e(url('/reservations/create')) ?>" class="btn-action">
                    <span class="material-symbols-outlined text-[18px]">add</span>
                    New Reservation
                </a>
            <?php endif; ?>
        </div>
    </div>

    <form method="get" action="<?= e(url('/reservations')) ?>" class="flex flex-wrap items-end gap-3">
        <div class="min-w-[180px] flex-1">
            <label class="label-caps mb-1 block text-outline" for="q">Search</label>
            <input id="q" name="q" type="search" class="input-field" placeholder="Guest, room, or reference"
                   value="<?= e((string) ($filters['q'] ?? '')) ?>">
        </div>
        <div>
            <label class="label-caps mb-1 block text-outline" for="status">Status</label>
            <select id="status" name="status" class="input-field">
                <option value="">All</option>
                <?php foreach (\App\Models\Reservation::STATUSES as $status): ?>
                    <option value="<?= e($status) ?>" <?= ($filters['status'] ?? '') === $status ? 'selected' : '' ?>>
                        <?= e($reservationService->labelForStatus($status)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="label-caps mb-1 block text-outline" for="from">From</label>
            <input id="from" name="from" type="date" class="input-field"
                   value="<?= e((string) ($filters['from'] ?? '')) ?>">
        </div>
        <div>
            <label class="label-caps mb-1 block text-outline" for="to">To</label>
            <input id="to" name="to" type="date" class="input-field"
                   value="<?= e((string) ($filters['to'] ?? '')) ?>">
        </div>
        <button type="submit" class="btn-primary">Filter</button>
    </form>

    <div class="surface-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-surface-container-low">
                    <tr>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Reference</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Guest</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Room</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Dates</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Source</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Status</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Rate</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($reservations === []): ?>
                        <tr>
                            <td colspan="8" class="px-cell-x py-8 text-center text-body-sm text-on-surface-variant">
                                No reservations match these filters.
                                <?php if ($canCreate): ?>
                                    <a class="font-semibold text-primary-container" href="<?= e(url('/reservations/create')) ?>">Create one</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($reservations as $row): ?>
                        <?php $chip = $reservationService->chipClasses((string) $row['status']); ?>
                        <tr class="border-t border-outline-variant hover:bg-primary/[0.02]">
                            <td class="px-cell-x py-cell-y data-mono text-body-sm">
                                <a class="hover:text-primary" href="<?= e(url('/reservations/' . $row['id'])) ?>">
                                    <?= e((string) $row['booking_reference']) ?>
                                </a>
                            </td>
                            <td class="px-cell-x py-cell-y">
                                <a class="text-title-sm text-on-surface hover:text-primary" href="<?= e(url('/guests/' . $row['guest_id'])) ?>">
                                    <?= e((string) $row['guest_name']) ?>
                                </a>
                                <p class="data-mono text-[11px] text-on-surface-variant"><?= e((string) ($row['guest_phone'] ?: '')) ?></p>
                            </td>
                            <td class="px-cell-x py-cell-y text-body-sm">
                                <span class="data-mono">#<?= e((string) $row['room_number']) ?></span>
                                · <?= e((string) $row['room_type_name']) ?>
                            </td>
                            <td class="px-cell-x py-cell-y data-mono text-body-sm text-on-surface-variant">
                                <?= e(format_date((string) $row['check_in_date'])) ?>
                                →
                                <?= e(format_date((string) $row['check_out_date'])) ?>
                            </td>
                            <td class="px-cell-x py-cell-y text-body-sm text-on-surface-variant">
                                <?= e($reservationService->labelForSource((string) $row['source'])) ?>
                            </td>
                            <td class="px-cell-x py-cell-y">
                                <span class="inline-block rounded-sm px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider <?= e($chip['bg']) ?> <?= e($chip['text']) ?>">
                                    <?= e($reservationService->labelForStatus((string) $row['status'])) ?>
                                </span>
                            </td>
                            <td class="px-cell-x py-cell-y data-mono text-body-sm"><?= e(format_money($row['agreed_rate'])) ?></td>
                            <td class="px-cell-x py-cell-y text-right">
                                <a class="btn-ghost" href="<?= e(url('/reservations/' . $row['id'])) ?>">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
