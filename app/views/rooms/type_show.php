<?php
/** @var array<string, mixed> $type */
/** @var list<string> $amenities */
/** @var list<array<string, mixed>> $ratePlans */
/** @var bool $canManage */
/** @var array<string, string> $rateErrors */
/** @var array<string, mixed> $rateOld */
?>
<div class="space-y-stack-gap">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-body-sm text-on-surface-variant">
                <a href="<?= e(url('/rooms/types')) ?>" class="hover:text-primary">Room Types</a> / Detail
            </p>
            <h1 class="text-headline-md text-on-surface"><?= e((string) $type['name']) ?></h1>
            <p class="text-body-sm text-on-surface-variant">
                <?= (int) $type['room_count'] ?> rooms · <?= (int) $type['rate_plan_count'] ?> rate plans
            </p>
        </div>
        <?php if ($canManage): ?>
            <div class="flex flex-wrap gap-2">
                <a href="<?= e(url('/rooms/types/' . $type['id'] . '/edit')) ?>" class="btn-outline">Edit</a>
                <form method="post" action="<?= e(url('/rooms/types/' . $type['id'] . '/delete')) ?>"
                      onsubmit="return confirm('Delete this room type? Rate plans will also be removed.');">
                    <?= \App\Core\CSRF::field() ?>
                    <button type="submit" class="btn-ghost text-error">Delete</button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <div class="grid gap-stack-gap lg:grid-cols-3">
        <div class="surface-card space-y-4 p-6 lg:col-span-2">
            <div>
                <p class="label-caps text-outline">Description</p>
                <p class="mt-1 text-body-md text-on-surface">
                    <?= e((string) ($type['description'] ?: 'No description.')) ?>
                </p>
            </div>

            <div>
                <p class="label-caps mb-2 text-outline">Capacity &amp; setup</p>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div class="rounded border border-outline-variant p-3">
                        <p class="text-[10px] text-outline">ADULTS</p>
                        <p class="data-mono text-title-sm"><?= (int) $type['base_capacity_adults'] ?></p>
                    </div>
                    <div class="rounded border border-outline-variant p-3">
                        <p class="text-[10px] text-outline">CHILDREN</p>
                        <p class="data-mono text-title-sm"><?= (int) $type['base_capacity_children'] ?></p>
                    </div>
                    <div class="rounded border border-outline-variant p-3">
                        <p class="text-[10px] text-outline">BASE RATE</p>
                        <p class="data-mono text-title-sm"><?= e(format_money($type['base_rate'])) ?></p>
                    </div>
                    <div class="rounded border border-outline-variant p-3">
                        <p class="text-[10px] text-outline">EXTRA BED</p>
                        <p class="data-mono text-title-sm">
                            <?= $type['extra_bed_rate'] !== null ? e(format_money($type['extra_bed_rate'])) : '—' ?>
                        </p>
                    </div>
                </div>
            </div>

            <div>
                <p class="label-caps mb-2 text-outline">Amenities</p>
                <?php if ($amenities === []): ?>
                    <p class="text-body-sm text-on-surface-variant">No amenities listed.</p>
                <?php else: ?>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($amenities as $amenity): ?>
                            <span class="rounded-sm bg-primary-fixed px-2 py-1 text-[10px] font-bold text-on-primary-fixed-variant">
                                <?= e($amenity) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="surface-card p-6">
            <p class="label-caps text-outline">Quick links</p>
            <p class="mt-2 text-body-sm text-on-surface-variant">
                Physical rooms inventory arrives in feature 06. This type is ready to attach rooms once that ships.
            </p>
        </div>
    </div>

    <div class="surface-card overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-outline-variant px-4 py-3">
            <h2 class="text-title-sm text-on-surface">Rate plans</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-surface-container-low">
                    <tr>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Name</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Rate</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Window</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Status</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($ratePlans === []): ?>
                        <tr>
                            <td colspan="5" class="px-cell-x py-6 text-center text-body-sm text-on-surface-variant">
                                No rate plans yet. Base rate above applies by default.
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($ratePlans as $plan): ?>
                        <tr class="border-t border-outline-variant">
                            <td class="px-cell-x py-cell-y text-body-sm text-on-surface"><?= e((string) $plan['name']) ?></td>
                            <td class="px-cell-x py-cell-y data-mono"><?= e(format_money($plan['rate'])) ?></td>
                            <td class="px-cell-x py-cell-y text-body-sm text-on-surface-variant">
                                <?php
                                $start = $plan['start_date'] ? format_date((string) $plan['start_date']) : 'Anytime';
                                $end = $plan['end_date'] ? format_date((string) $plan['end_date']) : 'Open';
                                echo e($start . ' – ' . $end);
                                ?>
                            </td>
                            <td class="px-cell-x py-cell-y">
                                <?php if ((int) $plan['is_active'] === 1): ?>
                                    <span class="rounded-sm bg-primary-fixed px-2 py-0.5 text-[10px] font-bold text-on-primary-fixed-variant">ACTIVE</span>
                                <?php else: ?>
                                    <span class="rounded-sm bg-surface-variant px-2 py-0.5 text-[10px] font-bold text-on-surface-variant">INACTIVE</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-cell-x py-cell-y text-right">
                                <?php if ($canManage): ?>
                                    <form method="post" action="<?= e(url('/rooms/types/' . $type['id'] . '/rates/' . $plan['id'] . '/delete')) ?>"
                                          class="inline" onsubmit="return confirm('Remove this rate plan?');">
                                        <?= \App\Core\CSRF::field() ?>
                                        <button type="submit" class="btn-ghost text-error">Remove</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($canManage): ?>
            <form method="post" action="<?= e(url('/rooms/types/' . $type['id'] . '/rates')) ?>"
                  class="space-y-3 border-t border-outline-variant bg-surface-container-low p-4">
                <?= \App\Core\CSRF::field() ?>
                <p class="label-caps text-outline">Add rate plan</p>
                <div class="grid gap-3 md:grid-cols-5">
                    <div class="md:col-span-2">
                        <input name="name" class="input-field" placeholder="Weekend Rate" required
                               value="<?= e((string) ($rateOld['name'] ?? '')) ?>">
                        <?php if (!empty($rateErrors['name'])): ?>
                            <p class="mt-1 text-body-sm text-error"><?= e($rateErrors['name']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <input type="number" step="0.01" min="0" name="rate" class="input-field data-mono" placeholder="Rate" required
                               value="<?= e((string) ($rateOld['rate'] ?? '')) ?>">
                        <?php if (!empty($rateErrors['rate'])): ?>
                            <p class="mt-1 text-body-sm text-error"><?= e($rateErrors['rate']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <input type="date" name="start_date" class="input-field"
                               value="<?= e((string) ($rateOld['start_date'] ?? '')) ?>">
                    </div>
                    <div>
                        <input type="date" name="end_date" class="input-field"
                               value="<?= e((string) ($rateOld['end_date'] ?? '')) ?>">
                    </div>
                </div>
                <label class="flex items-center gap-2 text-body-sm text-on-surface">
                    <input type="checkbox" name="is_active" value="1" class="rounded border-outline-variant text-primary"
                        <?= !isset($rateOld['is_active']) || !empty($rateOld['is_active']) ? 'checked' : '' ?>>
                    Active
                </label>
                <button type="submit" class="btn-primary">Add rate plan</button>
            </form>
        <?php endif; ?>
    </div>
</div>
