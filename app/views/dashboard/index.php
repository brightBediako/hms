<?php
/** @var array<string, mixed> $snapshot */
/** @var \App\Services\ReportService $reportService */
/** @var \App\Services\ReservationService $reservationService */
/** @var bool $canReports */
/** @var array<string, mixed>|null $user */

$rooms = $snapshot['rooms'];
?>
<div class="space-y-stack-gap">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-headline-md text-primary">Dashboard</h1>
            <p class="text-body-sm text-on-surface-variant">
                Live snapshot for <?= e(format_date((string) $snapshot['today'])) ?>
                · <?= e((string) ($user['full_name'] ?? '')) ?>
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="<?= e(url('/frontdesk')) ?>" class="btn-outline">Front Desk</a>
            <?php if ($canReports): ?>
                <a href="<?= e(url('/reports')) ?>" class="btn-primary">Reports</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="grid gap-stack-gap sm:grid-cols-2 lg:grid-cols-4">
        <div class="surface-card p-4">
            <p class="label-caps text-outline">Occupancy</p>
            <p class="mt-1 data-mono text-display-lg text-primary"><?= e(number_format((float) $rooms['occupancy_pct'], 1)) ?>%</p>
            <p class="text-body-sm text-on-surface-variant">
                <span class="data-mono"><?= (int) $rooms['occupied'] ?></span> / <?= (int) $rooms['total'] ?> rooms occupied
            </p>
        </div>
        <div class="surface-card p-4">
            <p class="label-caps text-outline">Available</p>
            <p class="mt-1 data-mono text-display-lg text-primary"><?= (int) $rooms['available'] ?></p>
            <p class="text-body-sm text-on-surface-variant">
                Reserved <?= (int) $rooms['reserved'] ?>
                · Cleaning <?= (int) $rooms['cleaning'] ?>
                · Maint. <?= (int) $rooms['maintenance'] ?>
            </p>
        </div>
        <div class="surface-card p-4">
            <p class="label-caps text-outline">Today’s revenue</p>
            <p class="mt-1 data-mono text-display-lg text-primary"><?= e(format_money($snapshot['revenue_today'])) ?></p>
            <p class="text-body-sm text-on-surface-variant">Payments recorded today</p>
        </div>
        <div class="surface-card p-4">
            <p class="label-caps text-outline">Outstanding</p>
            <p class="mt-1 data-mono text-display-lg text-primary"><?= e(format_money($snapshot['outstanding_balance'])) ?></p>
            <p class="text-body-sm text-on-surface-variant">Issued / partially paid invoices</p>
        </div>
    </div>

    <div class="grid gap-stack-gap lg:grid-cols-3">
        <div class="surface-card p-4">
            <p class="label-caps text-outline">In house</p>
            <p class="mt-1 data-mono text-headline-md"><?= (int) $snapshot['in_house_count'] ?></p>
        </div>
        <div class="surface-card p-4">
            <p class="label-caps text-outline">Arrivals today</p>
            <p class="mt-1 data-mono text-headline-md"><?= (int) $snapshot['arrivals_count'] ?></p>
        </div>
        <div class="surface-card p-4">
            <p class="label-caps text-outline">Departures today</p>
            <p class="mt-1 data-mono text-headline-md"><?= (int) $snapshot['departures_count'] ?></p>
        </div>
    </div>

    <div class="grid gap-stack-gap lg:grid-cols-2">
        <section class="surface-card overflow-hidden">
            <div class="flex items-center justify-between border-b border-outline-variant px-4 py-3">
                <h2 class="text-title-sm">Arrivals</h2>
                <a class="btn-ghost" href="<?= e(url('/frontdesk')) ?>">Open</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-surface-container-low">
                        <tr>
                            <th class="label-caps px-cell-x py-cell-y text-outline">Guest</th>
                            <th class="label-caps px-cell-x py-cell-y text-outline">Room</th>
                            <th class="label-caps px-cell-x py-cell-y text-outline">Ref</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($snapshot['arrivals'] === []): ?>
                            <tr>
                                <td colspan="3" class="px-cell-x py-6 text-center text-body-sm text-on-surface-variant">No arrivals due.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($snapshot['arrivals'] as $row): ?>
                            <tr class="border-t border-outline-variant">
                                <td class="px-cell-x py-cell-y text-body-sm"><?= e((string) $row['guest_name']) ?></td>
                                <td class="px-cell-x py-cell-y data-mono text-body-sm">#<?= e((string) $row['room_number']) ?></td>
                                <td class="px-cell-x py-cell-y data-mono text-[11px] text-on-surface-variant"><?= e((string) $row['booking_reference']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="surface-card overflow-hidden">
            <div class="flex items-center justify-between border-b border-outline-variant px-4 py-3">
                <h2 class="text-title-sm">Departures</h2>
                <a class="btn-ghost" href="<?= e(url('/frontdesk')) ?>">Open</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-surface-container-low">
                        <tr>
                            <th class="label-caps px-cell-x py-cell-y text-outline">Guest</th>
                            <th class="label-caps px-cell-x py-cell-y text-outline">Room</th>
                            <th class="label-caps px-cell-x py-cell-y text-outline">Ref</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($snapshot['departures'] === []): ?>
                            <tr>
                                <td colspan="3" class="px-cell-x py-cell-y py-6 text-center text-body-sm text-on-surface-variant">No departures due.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($snapshot['departures'] as $row): ?>
                            <tr class="border-t border-outline-variant">
                                <td class="px-cell-x py-cell-y text-body-sm"><?= e((string) $row['guest_name']) ?></td>
                                <td class="px-cell-x py-cell-y data-mono text-body-sm">#<?= e((string) $row['room_number']) ?></td>
                                <td class="px-cell-x py-cell-y data-mono text-[11px] text-on-surface-variant"><?= e((string) $row['booking_reference']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <section class="surface-card overflow-hidden">
        <div class="flex items-center justify-between border-b border-outline-variant px-4 py-3">
            <h2 class="text-title-sm">Recent reservations</h2>
            <a class="btn-ghost" href="<?= e(url('/reservations')) ?>">View all</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-surface-container-low">
                    <tr>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Reference</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Guest</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Room</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Dates</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($snapshot['recent_reservations'] === []): ?>
                        <tr>
                            <td colspan="5" class="px-cell-x py-6 text-center text-body-sm text-on-surface-variant">No reservations yet.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($snapshot['recent_reservations'] as $row): ?>
                        <tr class="border-t border-outline-variant hover:bg-primary/[0.02]">
                            <td class="px-cell-x py-cell-y">
                                <a class="data-mono text-body-sm hover:text-primary" href="<?= e(url('/reservations/' . $row['id'])) ?>">
                                    <?= e((string) $row['booking_reference']) ?>
                                </a>
                            </td>
                            <td class="px-cell-x py-cell-y text-body-sm"><?= e((string) $row['guest_name']) ?></td>
                            <td class="px-cell-x py-cell-y data-mono text-body-sm">#<?= e((string) $row['room_number']) ?></td>
                            <td class="px-cell-x py-cell-y data-mono text-[11px] text-on-surface-variant">
                                <?= e(format_date((string) $row['check_in_date'])) ?> → <?= e(format_date((string) $row['check_out_date'])) ?>
                            </td>
                            <td class="px-cell-x py-cell-y text-body-sm">
                                <?= e($reservationService->labelForStatus((string) $row['status'])) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
