<?php
/** @var list<array<string, mixed>> $rooms */
/** @var list<array<string, mixed>> $staffList */
/** @var array<string, string> $errors */
/** @var array<string, mixed> $old */
/** @var \App\Services\HousekeepingService $hkService */

$value = static function (string $key, mixed $default = '') use ($old): string {
    if (array_key_exists($key, $old) && $old[$key] !== null) {
        return (string) $old[$key];
    }

    return (string) $default;
};
?>
<div class="mx-auto max-w-xl space-y-stack-gap">
    <div>
        <p class="text-body-sm text-on-surface-variant">
            <a href="<?= e(url('/housekeeping')) ?>" class="hover:text-primary">Housekeeping</a> / New
        </p>
        <h1 class="text-headline-md text-on-surface">New housekeeping task</h1>
    </div>

    <form method="post" action="<?= e(url('/housekeeping')) ?>" class="surface-card space-y-4 p-6">
        <?= \App\Core\CSRF::field() ?>

        <div>
            <label class="label-caps mb-2 block text-outline" for="room_id">Room</label>
            <select id="room_id" name="room_id" class="input-field" required>
                <option value="">Select room…</option>
                <?php foreach ($rooms as $room): ?>
                    <option value="<?= (int) $room['id'] ?>" <?= $value('room_id') === (string) $room['id'] ? 'selected' : '' ?>>
                        #<?= e((string) $room['room_number']) ?>
                        · <?= e((string) $room['room_type_name']) ?>
                        (<?= e((string) $room['status']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($errors['room_id'])): ?>
                <p class="mt-1 text-body-sm text-error"><?= e($errors['room_id']) ?></p>
            <?php endif; ?>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label-caps mb-2 block text-outline" for="task_type">Task type</label>
                <select id="task_type" name="task_type" class="input-field" required>
                    <?php foreach (\App\Models\HousekeepingTask::TYPES as $type): ?>
                        <option value="<?= e($type) ?>" <?= $value('task_type', 'daily_clean') === $type ? 'selected' : '' ?>>
                            <?= e($hkService->labelForType($type)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="label-caps mb-2 block text-outline" for="scheduled_for">Scheduled for</label>
                <input id="scheduled_for" name="scheduled_for" type="date" class="input-field"
                       value="<?= e($value('scheduled_for', date('Y-m-d'))) ?>">
            </div>
        </div>

        <div>
            <label class="label-caps mb-2 block text-outline" for="assigned_to">Assign to</label>
            <select id="assigned_to" name="assigned_to" class="input-field">
                <option value="">Unassigned</option>
                <?php foreach ($staffList as $member): ?>
                    <option value="<?= (int) $member['id'] ?>" <?= $value('assigned_to') === (string) $member['id'] ? 'selected' : '' ?>>
                        <?= e((string) $member['full_name']) ?> · <?= e((string) $member['role_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="label-caps mb-2 block text-outline" for="notes">Notes</label>
            <textarea id="notes" name="notes" rows="3" class="input-field resize-none"><?= e($value('notes')) ?></textarea>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="btn-action">Create task</button>
            <a href="<?= e(url('/housekeeping')) ?>" class="btn-outline">Cancel</a>
        </div>
    </form>
</div>
