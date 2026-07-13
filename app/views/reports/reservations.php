<?php
/** @var array<string, mixed> $data */
/** @var string $from */
/** @var string $to */
/** @var \App\Services\ReportService $reportService */
/** @var \App\Services\ReservationService $reservationService */
/** @var bool $printMode */
$actionPath = '/reports/reservations';
?>
<div class="space-y-stack-gap">
    <div>
        <?php if (empty($printMode)): ?>
            <p class="text-body-sm text-on-surface-variant">
                <a href="<?= e(url('/reports')) ?>" class="hover:text-primary">Reports</a> / Reservations
            </p>
        <?php else: ?>
            <p class="label-caps text-outline"><?= e(hotel_name()) ?></p>
        <?php endif; ?>
        <h1 class="text-headline-md text-on-surface">Reservations report</h1>
        <p class="text-body-sm text-on-surface-variant">
            Check-ins from <?= e(format_date($data['from'])) ?> to <?= e(format_date($data['to'])) ?>
            · <?= (int) $data['total'] ?> booking(s)
        </p>
    </div>

    <?php require __DIR__ . '/_range_filter.php'; ?>

    <div class="surface-card overflow-hidden">
        <div class="border-b border-outline-variant px-4 py-3"><h2 class="text-title-sm">By status</h2></div>
        <table class="w-full text-left">
            <thead class="bg-surface-container-low">
                <tr>
                    <th class="label-caps px-cell-x py-cell-y text-outline">Status</th>
                    <th class="label-caps px-cell-x py-cell-y text-outline text-right">Count</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($data['by_status'] === []): ?>
                    <tr><td colspan="2" class="px-cell-x py-6 text-center text-body-sm text-on-surface-variant">No reservations in range.</td></tr>
                <?php endif; ?>
                <?php foreach ($data['by_status'] as $row): ?>
                    <tr class="border-t border-outline-variant">
                        <td class="px-cell-x py-cell-y text-body-sm"><?= e($reservationService->labelForStatus((string) $row['status'])) ?></td>
                        <td class="px-cell-x py-cell-y data-mono text-right"><?= (int) $row['count'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="surface-card overflow-hidden">
        <div class="border-b border-outline-variant px-4 py-3"><h2 class="text-title-sm">Detail</h2></div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-surface-container-low">
                    <tr>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Reference</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Guest</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Room</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Check-in</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['reservations'] as $row): ?>
                        <tr class="border-t border-outline-variant">
                            <td class="px-cell-x py-cell-y data-mono text-body-sm"><?= e((string) $row['booking_reference']) ?></td>
                            <td class="px-cell-x py-cell-y text-body-sm"><?= e((string) $row['guest_name']) ?></td>
                            <td class="px-cell-x py-cell-y data-mono text-body-sm">#<?= e((string) $row['room_number']) ?></td>
                            <td class="px-cell-x py-cell-y data-mono text-[11px]"><?= e(format_date((string) $row['check_in_date'])) ?></td>
                            <td class="px-cell-x py-cell-y text-body-sm"><?= e($reservationService->labelForStatus((string) $row['status'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
