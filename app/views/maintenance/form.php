<?php
/** @var list<array<string, mixed>> $rooms */
/** @var list<array<string, mixed>> $staffList */
/** @var array<string, string> $errors */
/** @var array<string, mixed> $old */
/** @var \App\Services\MaintenanceService $maintenanceService */
/** @var int $prefillRoomId */

$value = static function (string $key, mixed $default = '') use ($old, $prefillRoomId): string {
    if (array_key_exists($key, $old) && $old[$key] !== null) {
        return (string) $old[$key];
    }
    if ($key === 'room_id' && $prefillRoomId > 0) {
        return (string) $prefillRoomId;
    }

    return (string) $default;
};
?>
<div class="mx-auto max-w-xl space-y-stack-gap">
    <div>
        <p class="text-body-sm text-on-surface-variant">
            <a href="<?= e(url('/maintenance')) ?>" class="hover:text-primary">Maintenance</a> / New
        </p>
        <h1 class="text-headline-md text-on-surface">New maintenance request</h1>
    </div>

    <form method="post" action="<?= e(url('/maintenance')) ?>" class="surface-card space-y-4 p-6">
        <?= \App\Core\CSRF::field() ?>

        <div>
            <label class="label-caps mb-2 block text-outline" for="issue_title">Issue title</label>
            <input id="issue_title" name="issue_title" class="input-field" required maxlength="150"
                   value="<?= e($value('issue_title')) ?>" placeholder="AC not cooling">
            <?php if (!empty($errors['issue_title'])): ?>
                <p class="mt-1 text-body-sm text-error"><?= e($errors['issue_title']) ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label class="label-caps mb-2 block text-outline" for="description">Description</label>
            <textarea id="description" name="description" rows="4" class="input-field resize-none"><?= e($value('description')) ?></textarea>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label-caps mb-2 block text-outline" for="priority">Priority</label>
                <select id="priority" name="priority" class="input-field" required>
                    <?php foreach (\App\Models\MaintenanceRequest::PRIORITIES as $priority): ?>
                        <option value="<?= e($priority) ?>" <?= $value('priority', 'medium') === $priority ? 'selected' : '' ?>>
                            <?= e($maintenanceService->labelForPriority($priority)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="label-caps mb-2 block text-outline" for="room_id">Room (optional)</label>
                <select id="room_id" name="room_id" class="input-field">
                    <option value="">Common area / none</option>
                    <?php foreach ($rooms as $room): ?>
                        <option value="<?= (int) $room['id'] ?>" <?= $value('room_id') === (string) $room['id'] ? 'selected' : '' ?>>
                            #<?= e((string) $room['room_number']) ?>
                            · <?= e((string) $room['room_type_name']) ?>
                            (<?= e((string) $room['status']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="mt-1 text-[11px] text-on-surface-variant">
                    Linking a non-occupied room sets it to Maintenance (blocks bookings).
                </p>
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

        <div class="flex gap-2">
            <button type="submit" class="btn-action">Create request</button>
            <a href="<?= e(url('/maintenance')) ?>" class="btn-outline">Cancel</a>
        </div>
    </form>
</div>
