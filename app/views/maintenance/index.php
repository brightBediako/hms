<?php
/** @var list<array<string, mixed>> $requests */
/** @var array<string, int> $counts */
/** @var array{status:?string,priority:?string,q:?string} $filters */
/** @var bool $canManage */
/** @var \App\Services\MaintenanceService $maintenanceService */
/** @var \App\Services\RoomService $roomService */

$openCount = ($counts['open'] ?? 0) + ($counts['in_progress'] ?? 0);
?>
<div class="space-y-stack-gap">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-headline-md text-primary">Maintenance</h1>
            <p class="text-body-sm text-on-surface-variant">
                Work requests that take rooms out of inventory until resolved.
            </p>
        </div>
        <?php if ($canManage): ?>
            <a href="<?= e(url('/maintenance/create')) ?>" class="btn-action">
                <span class="material-symbols-outlined text-[18px]">add</span>
                New request
            </a>
        <?php endif; ?>
    </div>

    <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
        <?php
        $summary = [
            ['open', 'Open', 'report'],
            ['in_progress', 'In progress', 'build'],
            ['resolved', 'Resolved', 'check_circle'],
            ['cancelled', 'Cancelled', 'cancel'],
        ];
        foreach ($summary as [$key, $label, $icon]):
            $chip = $maintenanceService->statusChipClasses($key);
        ?>
            <a href="<?= e(url('/maintenance?status=' . urlencode($key))) ?>"
               class="surface-card flex items-center gap-3 p-3 hover:border-primary">
                <div class="flex h-10 w-10 items-center justify-center rounded <?= e($chip['bg']) ?> <?= e($chip['text']) ?>">
                    <span class="material-symbols-outlined"><?= e($icon) ?></span>
                </div>
                <div>
                    <p class="label-caps text-outline"><?= e($label) ?></p>
                    <p class="data-mono text-title-sm"><?= (int) ($counts[$key] ?? 0) ?></p>
                </div>
            </a>
        <?php endforeach; ?>
    </div>

    <form method="get" action="<?= e(url('/maintenance')) ?>" class="flex flex-wrap items-end gap-3">
        <div class="min-w-[180px] flex-1">
            <label class="label-caps mb-1 block text-outline" for="q">Search</label>
            <input id="q" name="q" type="search" class="input-field" placeholder="Title, room, assignee…"
                   value="<?= e((string) ($filters['q'] ?? '')) ?>">
        </div>
        <div>
            <label class="label-caps mb-1 block text-outline" for="status">Status</label>
            <select id="status" name="status" class="input-field">
                <option value="">All</option>
                <?php foreach (\App\Models\MaintenanceRequest::STATUSES as $status): ?>
                    <option value="<?= e($status) ?>" <?= ($filters['status'] ?? '') === $status ? 'selected' : '' ?>>
                        <?= e($maintenanceService->labelForStatus($status)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="label-caps mb-1 block text-outline" for="priority">Priority</label>
            <select id="priority" name="priority" class="input-field">
                <option value="">All</option>
                <?php foreach (\App\Models\MaintenanceRequest::PRIORITIES as $priority): ?>
                    <option value="<?= e($priority) ?>" <?= ($filters['priority'] ?? '') === $priority ? 'selected' : '' ?>>
                        <?= e($maintenanceService->labelForPriority($priority)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn-primary">Filter</button>
    </form>

    <p class="text-body-sm text-on-surface-variant"><?= (int) $openCount ?> open request(s)</p>

    <div class="surface-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-surface-container-low">
                    <tr>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Issue</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Room</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Priority</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Status</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Assignee</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Reported</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($requests === []): ?>
                        <tr>
                            <td colspan="7" class="px-cell-x py-8 text-center text-body-sm text-on-surface-variant">
                                No maintenance requests.
                                <?php if ($canManage): ?>
                                    <a class="font-semibold text-primary-container" href="<?= e(url('/maintenance/create')) ?>">Create one</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($requests as $row): ?>
                        <?php
                        $statusChip = $maintenanceService->statusChipClasses((string) $row['status']);
                        $priorityChip = $maintenanceService->priorityChipClasses((string) $row['priority']);
                        ?>
                        <tr class="border-t border-outline-variant hover:bg-primary/[0.02]">
                            <td class="px-cell-x py-cell-y">
                                <a href="<?= e(url('/maintenance/' . $row['id'])) ?>" class="text-title-sm text-on-surface hover:text-primary">
                                    <?= e((string) $row['issue_title']) ?>
                                </a>
                                <?php if (!empty($row['description'])): ?>
                                    <p class="line-clamp-1 text-[11px] text-on-surface-variant"><?= e((string) $row['description']) ?></p>
                                <?php endif; ?>
                            </td>
                            <td class="px-cell-x py-cell-y text-body-sm">
                                <?php if ($row['room_id']): ?>
                                    <span class="data-mono">#<?= e((string) $row['room_number']) ?></span>
                                    <?php if (!empty($row['room_status'])): ?>
                                        <p class="text-[10px] text-on-surface-variant"><?= e($roomService->labelForStatus((string) $row['room_status'])) ?></p>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-on-surface-variant">Common area</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-cell-x py-cell-y">
                                <span class="inline-block rounded-sm px-2 py-0.5 text-[10px] font-bold uppercase <?= e($priorityChip['bg']) ?> <?= e($priorityChip['text']) ?>">
                                    <?= e($maintenanceService->labelForPriority((string) $row['priority'])) ?>
                                </span>
                            </td>
                            <td class="px-cell-x py-cell-y">
                                <span class="inline-block rounded-sm px-2 py-0.5 text-[10px] font-bold uppercase <?= e($statusChip['bg']) ?> <?= e($statusChip['text']) ?>">
                                    <?= e($maintenanceService->labelForStatus((string) $row['status'])) ?>
                                </span>
                            </td>
                            <td class="px-cell-x py-cell-y text-body-sm text-on-surface-variant">
                                <?= e((string) ($row['assigned_to_name'] ?: 'Unassigned')) ?>
                            </td>
                            <td class="px-cell-x py-cell-y data-mono text-[11px] text-on-surface-variant">
                                <?= e(format_datetime((string) $row['reported_at'])) ?>
                            </td>
                            <td class="px-cell-x py-cell-y text-right">
                                <a class="btn-ghost" href="<?= e(url('/maintenance/' . $row['id'])) ?>">Open</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
