<?php
/** @var list<array<string, mixed>> $rooms */
/** @var array<string, mixed>|null $selected */
/** @var list<array<string, mixed>> $selectedHistory */
/** @var list<string> $selectedAmenities */
/** @var list<array<string, mixed>> $types */
/** @var list<string> $floors */
/** @var array{floor:?string,type_ids:list<int>,statuses:list<string>,q:?string} $filters */
/** @var array<string, int> $statusCounts */
/** @var int $occupancyPercent */
/** @var bool $canManage */
/** @var \App\Services\RoomService $roomService */
/** @var \App\Services\RoomTypeService $typeService */

$selectedStatuses = $filters['statuses'] ?? \App\Models\Room::STATUSES;
$selectedTypeIds = $filters['type_ids'] ?? [];
$queryBase = [];
if (!empty($filters['floor'])) {
    $queryBase['floor'] = $filters['floor'];
}
if (!empty($filters['q'])) {
    $queryBase['q'] = $filters['q'];
}
foreach ($selectedTypeIds as $tid) {
    $queryBase['type_ids'][] = $tid;
}
foreach ($selectedStatuses as $st) {
    $queryBase['statuses'][] = $st;
}

$buildUrl = static function (array $params) use ($queryBase): string {
    $merged = array_merge($queryBase, $params);
    return url('/rooms?' . http_build_query($merged));
};
?>
<div class="flex flex-col gap-stack-gap lg:h-[calc(100vh-56px-48px)] lg:flex-row lg:overflow-hidden">
    <!-- Left filters (room_management.html) -->
    <aside class="custom-scrollbar w-full shrink-0 space-y-6 overflow-y-auto lg:w-64 lg:pr-2">
        <form method="get" action="<?= e(url('/rooms')) ?>" class="space-y-4">
            <h3 class="label-caps text-on-surface-variant">Filter Inventory</h3>

            <div>
                <label class="label-caps mb-1 block text-outline" for="floor">Floor</label>
                <select id="floor" name="floor" class="input-field" onchange="this.form.submit()">
                    <option value="">All Floors</option>
                    <?php foreach ($floors as $floor): ?>
                        <option value="<?= e($floor) ?>" <?= ($filters['floor'] ?? '') === $floor ? 'selected' : '' ?>>
                            <?= e($floor) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="label-caps mb-1 block text-outline">Room type</label>
                <div class="mt-2 space-y-2">
                    <?php foreach ($types as $type): ?>
                        <label class="group flex cursor-pointer items-center gap-2">
                            <input type="checkbox" name="type_ids[]" value="<?= (int) $type['id'] ?>"
                                   class="h-4 w-4 rounded border-outline-variant text-primary focus:ring-primary"
                                   <?= $selectedTypeIds === [] || in_array((int) $type['id'], $selectedTypeIds, true) ? 'checked' : '' ?>
                                   onchange="this.form.submit()">
                            <span class="text-body-sm text-on-surface-variant group-hover:text-primary"><?= e((string) $type['name']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <?php if ($types === []): ?>
                    <p class="text-body-sm text-on-surface-variant">
                        <a class="text-primary-container font-semibold" href="<?= e(url('/rooms/types/create')) ?>">Create a room type</a> first.
                    </p>
                <?php endif; ?>
            </div>

            <div>
                <label class="label-caps mb-1 block text-outline">Status</label>
                <div class="mt-2 flex flex-wrap gap-2">
                    <?php foreach (\App\Models\Room::STATUSES as $status): ?>
                        <?php
                        $chip = $roomService->chipClasses($status);
                        $checked = in_array($status, $selectedStatuses, true);
                        ?>
                        <label class="cursor-pointer">
                            <input type="checkbox" name="statuses[]" value="<?= e($status) ?>" class="peer sr-only"
                                   <?= $checked ? 'checked' : '' ?> onchange="this.form.submit()">
                            <span class="inline-block rounded px-2 py-1 text-[10px] font-bold uppercase <?= e($chip['bg']) ?> <?= e($chip['text']) ?> border <?= $checked ? 'border-primary' : 'border-transparent' ?>">
                                <?= e($roomService->labelForStatus($status)) ?>
                                (<?= (int) ($statusCounts[$status] ?? 0) ?>)
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div>
                <label class="label-caps mb-1 block text-outline" for="q">Search</label>
                <input id="q" type="search" name="q" class="input-field" placeholder="Room # or notes"
                       value="<?= e((string) ($filters['q'] ?? '')) ?>">
            </div>

            <button type="submit" class="btn-primary w-full">Apply filters</button>
        </form>

        <div class="rounded border border-dashed border-outline-variant bg-surface-container-low p-4">
            <p class="label-caps mb-1 text-on-surface-variant">Today's Stats</p>
            <div class="flex items-baseline justify-between">
                <span class="text-display-lg text-primary"><?= (int) $occupancyPercent ?>%</span>
                <span class="text-body-sm text-on-surface-variant">Occupancy</span>
            </div>
            <div class="mt-2 h-1 w-full overflow-hidden rounded-full bg-outline-variant">
                <div class="h-full bg-primary" style="width: <?= (int) $occupancyPercent ?>%"></div>
            </div>
            <p class="mt-2 text-body-sm text-on-surface-variant">
                Occupied + reserved vs total rooms (manual statuses until Front Desk is live).
            </p>
        </div>
    </aside>

    <!-- Center grid -->
    <section class="custom-scrollbar min-w-0 flex-1 overflow-y-auto lg:pr-2">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-headline-md text-on-surface">Room Inventory</h2>
            <div class="flex gap-2">
                <?php if ($canManage): ?>
                    <a href="<?= e(url('/rooms/create')) ?>" class="btn-action">
                        <span class="material-symbols-outlined text-[18px]">add</span>
                        Add Room
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($rooms === []): ?>
            <div class="surface-card p-8 text-center text-body-sm text-on-surface-variant">
                No rooms match these filters.
                <?php if ($canManage): ?>
                    <a class="font-semibold text-primary-container" href="<?= e(url('/rooms/create')) ?>">Add a room</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <?php foreach ($rooms as $room): ?>
                    <?php
                    $isSelected = $selected && (int) $selected['id'] === (int) $room['id'];
                    $amenities = $typeService->decodeAmenities($room['room_type_amenities'] ?? null);
                    $href = $buildUrl(['selected' => (int) $room['id']]);
                    ?>
                    <a href="<?= e($href) ?>"
                       class="group relative rounded border border-outline-variant bg-surface p-4 transition-all hover:border-primary active:scale-[0.98] <?= $isSelected ? 'ring-2 ring-primary' : '' ?>">
                        <div class="mb-4 flex items-start justify-between">
                            <span class="data-mono text-outline">#<?= e((string) $room['room_number']) ?></span>
                            <?php
                            $status = (string) $room['status'];
                            require HMS_ROOT . '/app/views/partials/status-chip.php';
                            ?>
                        </div>
                        <div class="mb-4">
                            <p class="text-title-sm text-on-surface"><?= e((string) $room['room_type_name']) ?></p>
                            <p class="text-body-sm text-on-surface-variant">
                                <?= e((string) ($room['floor'] ?: '—')) ?>
                                · <?= e(format_money($room['base_rate'])) ?>/night
                            </p>
                        </div>
                        <div class="flex items-center justify-between text-on-surface-variant">
                            <div class="flex gap-1">
                                <?php foreach (array_slice($amenities, 0, 3) as $amenity): ?>
                                    <span class="rounded-sm bg-surface-container px-1.5 py-0.5 text-[10px]"><?= e($amenity) ?></span>
                                <?php endforeach; ?>
                            </div>
                            <span class="material-symbols-outlined text-[18px]">
                                <?= match ((string) $room['status']) {
                                    'available' => 'check_circle',
                                    'occupied' => 'meeting_room',
                                    'reserved' => 'schedule',
                                    'cleaning' => 'mop',
                                    'maintenance' => 'build',
                                    default => 'bed',
                                } ?>
                            </span>
                        </div>
                        <?php if (!empty($room['notes'])): ?>
                            <p class="mt-3 line-clamp-1 text-[11px] text-on-surface-variant"><?= e((string) $room['notes']) ?></p>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Right details panel -->
    <aside class="flex w-full shrink-0 flex-col overflow-hidden rounded-xl border border-outline-variant bg-surface lg:w-80">
        <?php if ($selected === null): ?>
            <div class="flex flex-1 items-center justify-center p-6 text-center text-body-sm text-on-surface-variant">
                Select a room to view details.
            </div>
        <?php else: ?>
            <div class="flex items-center justify-between border-b border-outline-variant p-4">
                <h3 class="text-title-sm text-on-surface">Room Details</h3>
                <span class="data-mono font-bold text-primary">#<?= e((string) $selected['room_number']) ?></span>
            </div>

            <div class="custom-scrollbar flex-1 space-y-6 overflow-y-auto p-6">
                <div>
                    <h2 class="mb-2 text-headline-md text-on-surface"><?= e((string) $selected['room_type_name']) ?></h2>
                    <?php $status = (string) $selected['status']; require HMS_ROOT . '/app/views/partials/status-chip.php'; ?>
                    <p class="mt-2 text-body-sm text-on-surface-variant">
                        <?= e((string) ($selected['floor'] ?: 'No floor set')) ?>
                    </p>
                </div>

                <div>
                    <label class="label-caps mb-2 block text-outline">Capacity &amp; setup</label>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="mb-1 text-[10px] text-outline">MAX ADULTS</p>
                            <p class="data-mono text-body-sm"><?= (int) $selected['base_capacity_adults'] ?></p>
                        </div>
                        <div>
                            <p class="mb-1 text-[10px] text-outline">MAX CHILDREN</p>
                            <p class="data-mono text-body-sm"><?= (int) $selected['base_capacity_children'] ?></p>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="label-caps mb-2 block text-outline">Amenities</label>
                    <div class="grid grid-cols-1 gap-2">
                        <?php if ($selectedAmenities === []): ?>
                            <p class="text-body-sm text-on-surface-variant">No amenities on this type.</p>
                        <?php endif; ?>
                        <?php foreach ($selectedAmenities as $amenity): ?>
                            <div class="flex items-center gap-3 rounded border border-outline-variant p-2">
                                <span class="material-symbols-outlined text-[18px] text-on-surface-variant">check</span>
                                <span class="text-body-sm text-on-surface"><?= e($amenity) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php if ($canManage): ?>
                    <form method="post" action="<?= e(url('/rooms/' . $selected['id'])) ?>" class="space-y-4">
                        <?= \App\Core\CSRF::field() ?>
                        <input type="hidden" name="room_number" value="<?= e((string) $selected['room_number']) ?>">

                        <div>
                            <label class="label-caps mb-2 block text-outline" for="room_type_id">Room type</label>
                            <select id="room_type_id" name="room_type_id" class="input-field" required>
                                <?php foreach ($types as $type): ?>
                                    <option value="<?= (int) $type['id'] ?>" <?= (int) $selected['room_type_id'] === (int) $type['id'] ? 'selected' : '' ?>>
                                        <?= e((string) $type['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="label-caps mb-2 block text-outline" for="floor_edit">Floor</label>
                            <input id="floor_edit" name="floor" class="input-field" value="<?= e((string) ($selected['floor'] ?? '')) ?>">
                        </div>

                        <div>
                            <label class="label-caps mb-2 block text-outline" for="status">Status</label>
                            <select id="status" name="status" class="input-field" required>
                                <?php foreach (\App\Models\Room::STATUSES as $statusOption): ?>
                                    <option value="<?= e($statusOption) ?>" <?= $selected['status'] === $statusOption ? 'selected' : '' ?>>
                                        <?= e($roomService->labelForStatus($statusOption)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="mt-1 text-[11px] text-on-surface-variant">
                                Changes are written to room_status_log. Front Desk / HK will drive this later.
                            </p>
                        </div>

                        <div>
                            <label class="label-caps mb-2 block text-outline" for="status_reason">Status reason</label>
                            <input id="status_reason" name="status_reason" class="input-field" placeholder="Optional note for the log">
                        </div>

                        <div>
                            <label class="label-caps mb-2 block text-outline" for="notes">Internal notes</label>
                            <textarea id="notes" name="notes" rows="3" class="input-field resize-none"
                                      placeholder="Add maintenance notes or guest preferences..."><?= e((string) ($selected['notes'] ?? '')) ?></textarea>
                        </div>

                        <button type="submit" class="btn-action w-full">Save Changes</button>
                    </form>
                <?php else: ?>
                    <div>
                        <label class="label-caps mb-2 block text-outline">Internal notes</label>
                        <p class="text-body-sm text-on-surface"><?= e((string) ($selected['notes'] ?: '—')) ?></p>
                    </div>
                <?php endif; ?>

                <?php if ($selectedHistory !== []): ?>
                    <div>
                        <label class="label-caps mb-2 block text-outline">Status history</label>
                        <ul class="space-y-2">
                            <?php foreach ($selectedHistory as $row): ?>
                                <li class="rounded border border-outline-variant px-3 py-2 text-body-sm">
                                    <span class="data-mono"><?= e((string) ($row['old_status'] ?? '—')) ?></span>
                                    →
                                    <span class="data-mono"><?= e((string) $row['new_status']) ?></span>
                                    <p class="text-[11px] text-on-surface-variant">
                                        <?= e(format_datetime((string) $row['changed_at'])) ?>
                                        <?php if (!empty($row['changed_by_name'])): ?>
                                            · <?= e((string) $row['changed_by_name']) ?>
                                        <?php endif; ?>
                                        <?php if (!empty($row['reason'])): ?>
                                            · <?= e((string) $row['reason']) ?>
                                        <?php endif; ?>
                                    </p>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </aside>
</div>
