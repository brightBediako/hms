<?php
/** @var array<string, mixed> $request */
/** @var list<array<string, mixed>> $staffList */
/** @var bool $canManage */
/** @var \App\Services\MaintenanceService $maintenanceService */
/** @var \App\Services\RoomService $roomService */

$statusChip = $maintenanceService->statusChipClasses((string) $request['status']);
$priorityChip = $maintenanceService->priorityChipClasses((string) $request['priority']);
$isOpen = in_array((string) $request['status'], \App\Models\MaintenanceRequest::OPEN_STATUSES, true);
?>
<div class="space-y-stack-gap">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-body-sm text-on-surface-variant">
                <a href="<?= e(url('/maintenance')) ?>" class="hover:text-primary">Maintenance</a> / Request
            </p>
            <h1 class="text-headline-md text-on-surface"><?= e((string) $request['issue_title']) ?></h1>
            <div class="mt-2 flex flex-wrap gap-2">
                <span class="inline-block rounded-sm px-2 py-0.5 text-[10px] font-bold uppercase <?= e($statusChip['bg']) ?> <?= e($statusChip['text']) ?>">
                    <?= e($maintenanceService->labelForStatus((string) $request['status'])) ?>
                </span>
                <span class="inline-block rounded-sm px-2 py-0.5 text-[10px] font-bold uppercase <?= e($priorityChip['bg']) ?> <?= e($priorityChip['text']) ?>">
                    <?= e($maintenanceService->labelForPriority((string) $request['priority'])) ?>
                </span>
            </div>
        </div>
    </div>

    <div class="grid gap-stack-gap lg:grid-cols-2">
        <section class="surface-card space-y-4 p-6">
            <h2 class="text-title-sm text-on-surface">Details</h2>
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <p class="label-caps mb-1 text-outline">Location</p>
                    <?php if ($request['room_id']): ?>
                        <p class="text-body-sm">
                            Room <span class="data-mono">#<?= e((string) $request['room_number']) ?></span>
                            · <?= e((string) ($request['room_type_name'] ?: '')) ?>
                        </p>
                        <p class="text-[11px] text-on-surface-variant">
                            Inventory: <?= e($roomService->labelForStatus((string) $request['room_status'])) ?>
                        </p>
                        <a class="btn-ghost mt-1" href="<?= e(url('/rooms?selected=' . (int) $request['room_id'])) ?>">View room</a>
                    <?php else: ?>
                        <p class="text-body-sm text-on-surface-variant">Common area / no room</p>
                    <?php endif; ?>
                </div>
                <div>
                    <p class="label-caps mb-1 text-outline">Reported</p>
                    <p class="data-mono text-body-sm"><?= e(format_datetime((string) $request['reported_at'])) ?></p>
                    <p class="text-[11px] text-on-surface-variant"><?= e((string) ($request['reported_by_name'] ?: '—')) ?></p>
                </div>
                <div>
                    <p class="label-caps mb-1 text-outline">Assignee</p>
                    <p class="text-body-sm"><?= e((string) ($request['assigned_to_name'] ?: 'Unassigned')) ?></p>
                </div>
                <div>
                    <p class="label-caps mb-1 text-outline">Resolved</p>
                    <p class="data-mono text-body-sm"><?= e($request['resolved_at'] ? format_datetime((string) $request['resolved_at']) : '—') ?></p>
                </div>
            </div>
            <div>
                <p class="label-caps mb-1 text-outline">Description</p>
                <p class="text-body-sm whitespace-pre-wrap"><?= e((string) ($request['description'] ?: '—')) ?></p>
            </div>
        </section>

        <?php if ($canManage): ?>
            <section class="surface-card space-y-4 p-6">
                <h2 class="text-title-sm text-on-surface">Actions</h2>

                <?php if ($isOpen): ?>
                    <form method="post" action="<?= e(url('/maintenance/' . $request['id'] . '/assign')) ?>" class="flex flex-wrap gap-2">
                        <?= \App\Core\CSRF::field() ?>
                        <select name="assigned_to" class="input-field flex-1">
                            <option value="">Unassigned</option>
                            <?php foreach ($staffList as $member): ?>
                                <option value="<?= (int) $member['id'] ?>" <?= (int) ($request['assigned_to'] ?? 0) === (int) $member['id'] ? 'selected' : '' ?>>
                                    <?= e((string) $member['full_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn-outline">Assign</button>
                    </form>

                    <div class="flex flex-wrap gap-2">
                        <?php if ($request['status'] === 'open'): ?>
                            <form method="post" action="<?= e(url('/maintenance/' . $request['id'] . '/start')) ?>">
                                <?= \App\Core\CSRF::field() ?>
                                <button type="submit" class="btn-primary">Start work</button>
                            </form>
                        <?php endif; ?>

                        <form method="post" action="<?= e(url('/maintenance/' . $request['id'] . '/resolve')) ?>">
                            <?= \App\Core\CSRF::field() ?>
                            <button type="submit" class="btn-action">Resolve</button>
                        </form>

                        <form method="post" action="<?= e(url('/maintenance/' . $request['id'] . '/cancel')) ?>"
                              onsubmit="return confirm('Cancel this maintenance request?');">
                            <?= \App\Core\CSRF::field() ?>
                            <button type="submit" class="btn-outline border-error text-error">Cancel</button>
                        </form>
                    </div>
                <?php else: ?>
                    <p class="text-body-sm text-on-surface-variant">This request is closed.</p>
                <?php endif; ?>

                <p class="text-[11px] text-on-surface-variant">
                    Resolving or cancelling the last open request on a Maintenance room returns it to Available
                    (or Cleaning if HK tasks remain). Occupied rooms are not taken offline until vacant.
                </p>
            </section>
        <?php endif; ?>
    </div>
</div>
