<?php
/** @var array<string, mixed> $task */
/** @var list<array<string, mixed>> $staffList */
/** @var bool $canManage */
/** @var \App\Services\HousekeepingService $hkService */
/** @var \App\Services\RoomService $roomService */

$taskChip = $hkService->chipClasses((string) $task['status']);
$roomChip = $roomService->chipClasses((string) $task['room_status']);
$isOpen = in_array((string) $task['status'], \App\Models\HousekeepingTask::OPEN_STATUSES, true);
?>
<div class="space-y-stack-gap">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-body-sm text-on-surface-variant">
                <a href="<?= e(url('/housekeeping')) ?>" class="hover:text-primary">Housekeeping</a> / Task
            </p>
            <h1 class="text-headline-md text-on-surface">
                Room <span class="data-mono">#<?= e((string) $task['room_number']) ?></span>
            </h1>
            <p class="text-body-sm text-on-surface-variant">
                <?= e((string) $task['room_type_name']) ?>
                · <?= e($hkService->labelForType((string) $task['task_type'])) ?>
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <span class="inline-block rounded-sm px-2 py-0.5 text-[10px] font-bold uppercase <?= e($taskChip['bg']) ?> <?= e($taskChip['text']) ?>">
                <?= e($hkService->labelForStatus((string) $task['status'])) ?>
            </span>
            <span class="inline-block rounded-sm px-2 py-0.5 text-[10px] font-bold uppercase <?= e($roomChip['bg']) ?> <?= e($roomChip['text']) ?>">
                Room: <?= e($roomService->labelForStatus((string) $task['room_status'])) ?>
            </span>
        </div>
    </div>

    <div class="grid gap-stack-gap lg:grid-cols-2">
        <section class="surface-card space-y-3 p-6">
            <h2 class="text-title-sm text-on-surface">Details</h2>
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <p class="label-caps mb-1 text-outline">Scheduled</p>
                    <p class="data-mono text-body-sm"><?= e($task['scheduled_for'] ? format_date((string) $task['scheduled_for']) : '—') ?></p>
                </div>
                <div>
                    <p class="label-caps mb-1 text-outline">Assignee</p>
                    <p class="text-body-sm"><?= e((string) ($task['assigned_to_name'] ?: 'Unassigned')) ?></p>
                </div>
                <div>
                    <p class="label-caps mb-1 text-outline">Started</p>
                    <p class="data-mono text-body-sm"><?= e($task['started_at'] ? format_datetime((string) $task['started_at']) : '—') ?></p>
                </div>
                <div>
                    <p class="label-caps mb-1 text-outline">Completed</p>
                    <p class="data-mono text-body-sm"><?= e($task['completed_at'] ? format_datetime((string) $task['completed_at']) : '—') ?></p>
                </div>
            </div>
            <div>
                <p class="label-caps mb-1 text-outline">Notes</p>
                <p class="text-body-sm whitespace-pre-wrap"><?= e((string) ($task['notes'] ?: '—')) ?></p>
            </div>
            <a class="btn-ghost" href="<?= e(url('/rooms?selected=' . (int) $task['room_id'])) ?>">View room inventory</a>
        </section>

        <?php if ($canManage): ?>
            <section class="surface-card space-y-4 p-6">
                <h2 class="text-title-sm text-on-surface">Actions</h2>

                <?php if ($isOpen): ?>
                    <form method="post" action="<?= e(url('/housekeeping/' . $task['id'] . '/assign')) ?>" class="flex flex-wrap gap-2">
                        <?= \App\Core\CSRF::field() ?>
                        <select name="assigned_to" class="input-field flex-1">
                            <option value="">Unassigned</option>
                            <?php foreach ($staffList as $member): ?>
                                <option value="<?= (int) $member['id'] ?>" <?= (int) ($task['assigned_to'] ?? 0) === (int) $member['id'] ? 'selected' : '' ?>>
                                    <?= e((string) $member['full_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn-outline">Assign</button>
                    </form>
                <?php endif; ?>

                <div class="flex flex-wrap gap-2">
                    <?php if ($task['status'] === 'pending'): ?>
                        <form method="post" action="<?= e(url('/housekeeping/' . $task['id'] . '/start')) ?>">
                            <?= \App\Core\CSRF::field() ?>
                            <button type="submit" class="btn-primary">Start cleaning</button>
                        </form>
                    <?php endif; ?>

                    <?php if ($isOpen): ?>
                        <form method="post" action="<?= e(url('/housekeeping/' . $task['id'] . '/complete')) ?>">
                            <?= \App\Core\CSRF::field() ?>
                            <button type="submit" class="btn-action">Mark completed</button>
                        </form>
                    <?php endif; ?>

                    <?php if ($task['status'] === 'completed'): ?>
                        <form method="post" action="<?= e(url('/housekeeping/' . $task['id'] . '/verify')) ?>">
                            <?= \App\Core\CSRF::field() ?>
                            <button type="submit" class="btn-action">Verify &amp; release</button>
                        </form>
                    <?php endif; ?>
                </div>

                <p class="text-[11px] text-on-surface-variant">
                    Completing or verifying a clean sets the room to Available only if it is in Cleaning,
                    has no in-house guest, no open maintenance, and no other open HK tasks.
                </p>
            </section>
        <?php endif; ?>
    </div>
</div>
