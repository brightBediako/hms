<?php
/** @var string $from */
/** @var list<string> $dates */
/** @var list<array<string, mixed>> $rooms */
/** @var bool $canCreate */
/** @var \App\Services\ReservationService $reservationService */

$dayCount = count($dates);
$prev = (new DateTimeImmutable($from))->modify('-7 days')->format('Y-m-d');
$next = (new DateTimeImmutable($from))->modify('+7 days')->format('Y-m-d');
?>
<div class="space-y-stack-gap">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-body-sm text-on-surface-variant">
                <a href="<?= e(url('/reservations')) ?>" class="hover:text-primary">Reservations</a> / Calendar
            </p>
            <h1 class="text-headline-md text-on-surface">Availability calendar</h1>
            <p class="text-body-sm text-on-surface-variant">
                Room × night tape chart. Hatched = booked · Solid teal = checked in.
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="<?= e(url('/reservations/calendar?from=' . urlencode($prev))) ?>" class="btn-outline">← Prev</a>
            <a href="<?= e(url('/reservations/calendar?from=' . urlencode(date('Y-m-d')))) ?>" class="btn-ghost">Today</a>
            <a href="<?= e(url('/reservations/calendar?from=' . urlencode($next))) ?>" class="btn-outline">Next →</a>
            <?php if ($canCreate): ?>
                <a href="<?= e(url('/reservations/create')) ?>" class="btn-action">New Reservation</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="surface-card overflow-hidden">
        <div class="overflow-x-auto">
            <div class="min-w-[900px]">
                <div class="grid border-b border-outline-variant bg-surface-container-low"
                     style="grid-template-columns: 140px repeat(<?= (int) $dayCount ?>, minmax(56px, 1fr));">
                    <div class="label-caps px-3 py-2 text-outline">Room</div>
                    <?php foreach ($dates as $date): ?>
                        <?php
                        $isWeekend = in_array((int) date('N', strtotime($date)), [6, 7], true);
                        $isToday = $date === date('Y-m-d');
                        ?>
                        <div class="border-l border-outline-variant px-1 py-2 text-center <?= $isWeekend ? 'bg-surface-container/60' : '' ?> <?= $isToday ? 'bg-primary-fixed/40' : '' ?>">
                            <p class="label-caps text-[9px] text-outline"><?= e(date('D', strtotime($date))) ?></p>
                            <p class="data-mono text-[11px]"><?= e(date('d', strtotime($date))) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($rooms === []): ?>
                    <p class="px-4 py-8 text-center text-body-sm text-on-surface-variant">No rooms in inventory.</p>
                <?php endif; ?>

                <?php foreach ($rooms as $room): ?>
                    <div class="relative grid h-12 border-b border-outline-variant hover:bg-surface-container-low/50"
                         style="grid-template-columns: 140px repeat(<?= (int) $dayCount ?>, minmax(56px, 1fr));">
                        <div class="flex items-center justify-between gap-1 border-r border-outline-variant px-3">
                            <span class="data-mono text-body-sm">#<?= e((string) $room['room_number']) ?></span>
                            <span class="truncate text-[10px] text-on-surface-variant"><?= e((string) $room['room_type_name']) ?></span>
                        </div>
                        <?php for ($i = 0; $i < $dayCount; $i++): ?>
                            <div class="border-l border-outline-variant/40"></div>
                        <?php endfor; ?>

                        <?php foreach ($room['bars'] as $bar): ?>
                            <?php
                            $isBooked = $bar['status'] === 'booked';
                            $barClass = $isBooked
                                ? 'hatched-bg border border-secondary text-on-secondary-fixed-variant bg-secondary-fixed'
                                : 'bg-primary text-on-primary border border-primary';
                            $span = (int) $bar['span'];
                            ?>
                            <a href="<?= e(url('/reservations/' . $bar['id'])) ?>"
                               class="absolute inset-y-1 z-10 flex items-center overflow-hidden rounded px-1 text-[10px] font-semibold <?= e($barClass) ?>"
                               style="left: calc(140px + (100% - 140px) * <?= ((int) $bar['col_start'] - 1) ?> / <?= (int) $dayCount ?>); width: calc((100% - 140px) * <?= $span ?> / <?= (int) $dayCount ?> - 4px); margin-left: 2px;"
                               title="<?= e($bar['reference'] . ' · ' . $bar['guest_name']) ?>">
                                <span class="truncate"><?= e($bar['guest_name']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
