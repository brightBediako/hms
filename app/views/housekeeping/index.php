<?php
/** @var string $date */
/** @var bool $allDates */
/** @var list<array<string, mixed>> $tasks */
/** @var array<string, int> $counts */
/** @var array<string, mixed> $filters */
/** @var list<array<string, mixed>> $staffList */
/** @var bool $canManage */
/** @var \App\Services\HousekeepingService $hkService */
/** @var \App\Services\RoomService $roomService */

$openCount = ($counts['pending'] ?? 0) + ($counts['in_progress'] ?? 0);
?>
<div class="space-y-stack-gap">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-headline-md text-primary">Housekeeping Operations</h1>
            <p class="text-body-sm text-on-surface-variant">
                Cleaning tasks from check-out handoffs and scheduled room turns.
            </p>
        </div>
        <?php if ($canManage): ?>
            <a href="<?= e(url('/housekeeping/create')) ?>" class="btn-action">
                <span class="material-symbols-outlined text-[18px]">add</span>
                New task
            </a>
        <?php endif; ?>
    </div>

    <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
        <?php
        $summary = [
            ['pending', 'Pending', 'pending_actions'],
            ['in_progress', 'In progress', 'mop'],
            ['completed', 'Completed', 'check_circle'],
            ['verified', 'Verified', 'verified'],
        ];
        foreach ($summary as [$key, $label, $icon]):
            $chip = $hkService->chipClasses($key);
        ?>
            <a href="<?= e(url('/housekeeping?' . http_build_query(array_filter([
                'date' => $allDates ? null : $date,
                'all_dates' => $allDates ? '1' : null,
                'status' => $key,
                'q' => $filters['q'] ?? null,
            ])))) ?>"
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

    <form method="get" action="<?= e(url('/housekeeping')) ?>" class="flex flex-wrap items-end gap-3">
        <div>
            <label class="label-caps mb-1 block text-outline" for="date">Schedule date</label>
            <input id="date" type="date" name="date" class="input-field" value="<?= e($date) ?>">
        </div>
        <label class="mb-2 flex items-center gap-2 text-body-sm">
            <input type="checkbox" name="all_dates" value="1" <?= $allDates ? 'checked' : '' ?>
                   class="h-4 w-4 rounded border-outline-variant text-primary" onchange="this.form.submit()">
            All dates
        </label>
        <div>
            <label class="label-caps mb-1 block text-outline" for="status">Status</label>
            <select id="status" name="status" class="input-field">
                <option value="">All</option>
                <?php foreach (\App\Models\HousekeepingTask::STATUSES as $status): ?>
                    <option value="<?= e($status) ?>" <?= ($filters['status'] ?? '') === $status ? 'selected' : '' ?>>
                        <?= e($hkService->labelForStatus($status)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="label-caps mb-1 block text-outline" for="task_type">Type</label>
            <select id="task_type" name="task_type" class="input-field">
                <option value="">All</option>
                <?php foreach (\App\Models\HousekeepingTask::TYPES as $type): ?>
                    <option value="<?= e($type) ?>" <?= ($filters['task_type'] ?? '') === $type ? 'selected' : '' ?>>
                        <?= e($hkService->labelForType($type)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="min-w-[160px] flex-1">
            <label class="label-caps mb-1 block text-outline" for="q">Search</label>
            <input id="q" name="q" type="search" class="input-field" placeholder="Room or staff…"
                   value="<?= e((string) ($filters['q'] ?? '')) ?>">
        </div>
        <button type="submit" class="btn-primary">Filter</button>
    </form>

    <p class="text-body-sm text-on-surface-variant">
        <?= (int) $openCount ?> open task(s) in current counts · Completing a clean sets the room to Available when safe.
    </p>

    <div class="surface-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-surface-container-low">
                    <tr>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Room</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Type</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Scheduled</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Assignee</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Room status</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Task</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($tasks === []): ?>
                        <tr>
                            <td colspan="7" class="px-cell-x py-8 text-center text-body-sm text-on-surface-variant">
                                No housekeeping tasks match these filters.
                                <?php if ($canManage): ?>
                                    <a class="font-semibold text-primary-container" href="<?= e(url('/housekeeping/create')) ?>">Create a task</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($tasks as $row): ?>
                        <?php
                        $taskChip = $hkService->chipClasses((string) $row['status']);
                        $roomChip = $roomService->chipClasses((string) $row['room_status']);
                        ?>
                        <tr class="border-t border-outline-variant hover:bg-primary/[0.02]">
                            <td class="px-cell-x py-cell-y">
                                <span class="data-mono text-body-sm font-semibold">#<?= e((string) $row['room_number']) ?></span>
                                <p class="text-[11px] text-on-surface-variant"><?= e((string) $row['room_type_name']) ?></p>
                            </td>
                            <td class="px-cell-x py-cell-y text-body-sm">
                                <?= e($hkService->labelForType((string) $row['task_type'])) ?>
                            </td>
                            <td class="px-cell-x py-cell-y data-mono text-body-sm text-on-surface-variant">
                                <?= e($row['scheduled_for'] ? format_date((string) $row['scheduled_for']) : '—') ?>
                            </td>
                            <td class="px-cell-x py-cell-y text-body-sm text-on-surface-variant">
                                <?= e((string) ($row['assigned_to_name'] ?: 'Unassigned')) ?>
                            </td>
                            <td class="px-cell-x py-cell-y">
                                <span class="inline-block rounded-sm px-2 py-0.5 text-[10px] font-bold uppercase <?= e($roomChip['bg']) ?> <?= e($roomChip['text']) ?>">
                                    <?= e($roomService->labelForStatus((string) $row['room_status'])) ?>
                                </span>
                            </td>
                            <td class="px-cell-x py-cell-y">
                                <span class="inline-block rounded-sm px-2 py-0.5 text-[10px] font-bold uppercase <?= e($taskChip['bg']) ?> <?= e($taskChip['text']) ?>">
                                    <?= e($hkService->labelForStatus((string) $row['status'])) ?>
                                </span>
                            </td>
                            <td class="px-cell-x py-cell-y text-right">
                                <a class="btn-ghost" href="<?= e(url('/housekeeping/' . $row['id'])) ?>">Open</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
