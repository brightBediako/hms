<?php
/** @var list<array<string, mixed>> $types */
/** @var bool $canManage */
?>
<div class="space-y-stack-gap">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-headline-md text-on-surface">Room Types &amp; Rates</h1>
            <p class="text-body-sm text-on-surface-variant">
                Base inventory categories used by room assignment and pricing.
            </p>
        </div>
        <?php if ($canManage): ?>
            <a href="<?= e(url('/rooms/types/create')) ?>" class="btn-action">
                <span class="material-symbols-outlined text-[18px]">add</span>
                New Room Type
            </a>
        <?php endif; ?>
    </div>

    <div class="surface-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-surface-container-low">
                    <tr>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Type</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Capacity</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Base rate</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Extra bed</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Plans</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Rooms</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($types === []): ?>
                        <tr>
                            <td colspan="7" class="px-cell-x py-8 text-center text-body-sm text-on-surface-variant">
                                No room types yet.
                                <?php if ($canManage): ?>
                                    <a class="text-primary-container font-semibold" href="<?= e(url('/rooms/types/create')) ?>">Create the first type</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($types as $type): ?>
                        <tr class="border-t border-outline-variant hover:bg-primary/[0.02]">
                            <td class="px-cell-x py-cell-y">
                                <a href="<?= e(url('/rooms/types/' . $type['id'])) ?>" class="text-title-sm text-on-surface hover:text-primary">
                                    <?= e((string) $type['name']) ?>
                                </a>
                                <?php if (!empty($type['description'])): ?>
                                    <p class="text-body-sm text-on-surface-variant line-clamp-1"><?= e((string) $type['description']) ?></p>
                                <?php endif; ?>
                            </td>
                            <td class="px-cell-x py-cell-y text-body-sm text-on-surface-variant">
                                <?= (int) $type['base_capacity_adults'] ?> adults
                                <?php if ((int) $type['base_capacity_children'] > 0): ?>
                                    · <?= (int) $type['base_capacity_children'] ?> children
                                <?php endif; ?>
                            </td>
                            <td class="px-cell-x py-cell-y data-mono text-on-surface"><?= e(format_money($type['base_rate'])) ?></td>
                            <td class="px-cell-x py-cell-y data-mono text-on-surface-variant">
                                <?= $type['extra_bed_rate'] !== null ? e(format_money($type['extra_bed_rate'])) : '—' ?>
                            </td>
                            <td class="px-cell-x py-cell-y data-mono text-on-surface-variant"><?= (int) $type['rate_plan_count'] ?></td>
                            <td class="px-cell-x py-cell-y data-mono text-on-surface-variant"><?= (int) $type['room_count'] ?></td>
                            <td class="px-cell-x py-cell-y text-right">
                                <a class="btn-ghost" href="<?= e(url('/rooms/types/' . $type['id'])) ?>">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
