<?php
/** @var string $from */
/** @var string $to */
/** @var array{from:string,to:string,revenue:float,expenses:float,profit:float,outstanding:float} $profit */
?>
<div class="space-y-stack-gap">
    <div>
        <h1 class="text-headline-md text-on-surface">Reports</h1>
        <p class="text-body-sm text-on-surface-variant">
            Aggregates from live reservations, payments, guests, and expenses.
            Default range: <?= e(format_date($from)) ?> – <?= e(format_date($to)) ?>
        </p>
    </div>

    <div class="grid gap-stack-gap sm:grid-cols-2 lg:grid-cols-4">
        <div class="surface-card p-4">
            <p class="label-caps text-outline">Revenue (MTD)</p>
            <p class="mt-1 data-mono text-title-sm font-semibold"><?= e(format_money($profit['revenue'])) ?></p>
        </div>
        <div class="surface-card p-4">
            <p class="label-caps text-outline">Expenses (MTD)</p>
            <p class="mt-1 data-mono text-title-sm font-semibold"><?= e(format_money($profit['expenses'])) ?></p>
        </div>
        <div class="surface-card p-4">
            <p class="label-caps text-outline">Profit (MTD)</p>
            <p class="mt-1 data-mono text-title-sm font-semibold"><?= e(format_money($profit['profit'])) ?></p>
        </div>
        <div class="surface-card p-4">
            <p class="label-caps text-outline">Outstanding</p>
            <p class="mt-1 data-mono text-title-sm font-semibold"><?= e(format_money($profit['outstanding'])) ?></p>
        </div>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        <?php
        $links = [
            ['/reports/occupancy', 'hotel', 'Occupancy', 'Room-nights and occupancy %'],
            ['/reports/revenue', 'payments', 'Revenue', 'Payments by method'],
            ['/reports/reservations', 'event_available', 'Reservations', 'Bookings by check-in date'],
            ['/reports/guests', 'group', 'Guests', 'New guest profiles in range'],
            ['/reports/expenses', 'receipt_long', 'Expenses', 'Spend by category'],
            ['/reports/profit', 'monitoring', 'Profit summary', 'Revenue minus expenses'],
        ];
        foreach ($links as [$path, $icon, $label, $help]):
        ?>
            <a href="<?= e(url($path . '?from=' . urlencode($from) . '&to=' . urlencode($to))) ?>"
               class="surface-card flex items-start gap-3 p-4 hover:border-primary">
                <span class="material-symbols-outlined text-primary"><?= e($icon) ?></span>
                <div>
                    <p class="text-title-sm text-on-surface"><?= e($label) ?></p>
                    <p class="text-body-sm text-on-surface-variant"><?= e($help) ?></p>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>
