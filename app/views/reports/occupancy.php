<?php
/** @var array<string, mixed> $data */
/** @var string $from */
/** @var string $to */
/** @var \App\Services\ReportService $reportService */
/** @var bool $printMode */
$actionPath = '/reports/occupancy';
?>
<div class="space-y-stack-gap">
    <div>
        <?php if (empty($printMode)): ?>
            <p class="text-body-sm text-on-surface-variant">
                <a href="<?= e(url('/reports')) ?>" class="hover:text-primary">Reports</a> / Occupancy
            </p>
        <?php else: ?>
            <p class="label-caps text-outline"><?= e(hotel_name()) ?></p>
        <?php endif; ?>
        <h1 class="text-headline-md text-on-surface">Occupancy report</h1>
        <p class="text-body-sm text-on-surface-variant">
            <?= e(format_date($data['from'])) ?> – <?= e(format_date($data['to'])) ?>
            (<?= (int) $data['days'] ?> day<?= (int) $data['days'] === 1 ? '' : 's' ?>)
        </p>
    </div>

    <?php require __DIR__ . '/_range_filter.php'; ?>

    <div class="grid gap-stack-gap sm:grid-cols-2 lg:grid-cols-4">
        <div class="surface-card p-4">
            <p class="label-caps text-outline">Occupancy</p>
            <p class="data-mono text-headline-md"><?= e(number_format((float) $data['occupancy_pct'], 1)) ?>%</p>
        </div>
        <div class="surface-card p-4">
            <p class="label-caps text-outline">Room-nights sold</p>
            <p class="data-mono text-headline-md"><?= e(number_format((float) $data['room_nights_occupied'], 1)) ?></p>
        </div>
        <div class="surface-card p-4">
            <p class="label-caps text-outline">Room-nights available</p>
            <p class="data-mono text-headline-md"><?= (int) $data['room_nights_available'] ?></p>
        </div>
        <div class="surface-card p-4">
            <p class="label-caps text-outline">Inventory</p>
            <p class="data-mono text-headline-md"><?= (int) $data['total_rooms'] ?></p>
        </div>
    </div>

    <div class="surface-card overflow-hidden">
        <div class="border-b border-outline-variant px-4 py-3">
            <h2 class="text-title-sm">Reservations overlapping range (by status)</h2>
        </div>
        <table class="w-full text-left">
            <thead class="bg-surface-container-low">
                <tr>
                    <th class="label-caps px-cell-x py-cell-y text-outline">Status</th>
                    <th class="label-caps px-cell-x py-cell-y text-outline text-right">Count</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($data['by_status'] === []): ?>
                    <tr><td colspan="2" class="px-cell-x py-6 text-center text-body-sm text-on-surface-variant">No overlapping reservations.</td></tr>
                <?php endif; ?>
                <?php foreach ($data['by_status'] as $row): ?>
                    <tr class="border-t border-outline-variant">
                        <td class="px-cell-x py-cell-y text-body-sm"><?= e($reportService->labelForReservationStatus((string) $row['status'])) ?></td>
                        <td class="px-cell-x py-cell-y data-mono text-right"><?= (int) $row['count'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
