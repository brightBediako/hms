<?php
/** @var array<string, mixed>|null $room */
/** @var list<array<string, mixed>> $types */
/** @var list<string> $statuses */
/** @var array<string, string> $errors */
/** @var array<string, mixed> $old */
/** @var \App\Services\RoomService $roomService */

$value = static function (string $key, mixed $default = '') use ($old, $room): string {
    if (array_key_exists($key, $old)) {
        return (string) $old[$key];
    }
    if ($room !== null && array_key_exists($key, $room) && $room[$key] !== null) {
        return (string) $room[$key];
    }

    return (string) $default;
};
?>
<div class="mx-auto max-w-xl space-y-stack-gap">
    <div>
        <p class="text-body-sm text-on-surface-variant">
            <a href="<?= e(url('/rooms')) ?>" class="hover:text-primary">Rooms</a> / Add
        </p>
        <h1 class="text-headline-md text-on-surface">Add Room</h1>
    </div>

    <?php if ($types === []): ?>
        <div class="surface-card p-6 text-body-sm text-on-surface-variant">
            Create a <a class="font-semibold text-primary-container" href="<?= e(url('/rooms/types/create')) ?>">room type</a> before adding rooms.
        </div>
    <?php else: ?>
        <form method="post" action="<?= e(url('/rooms')) ?>" class="surface-card space-y-4 p-6">
            <?= \App\Core\CSRF::field() ?>

            <div>
                <label class="label-caps mb-2 block text-outline" for="room_number">Room number</label>
                <input id="room_number" name="room_number" class="input-field data-mono" required maxlength="20"
                       value="<?= e($value('room_number')) ?>" placeholder="101">
                <?php if (!empty($errors['room_number'])): ?>
                    <p class="mt-1 text-body-sm text-error"><?= e($errors['room_number']) ?></p>
                <?php endif; ?>
            </div>

            <div>
                <label class="label-caps mb-2 block text-outline" for="room_type_id">Room type</label>
                <select id="room_type_id" name="room_type_id" class="input-field" required>
                    <?php foreach ($types as $type): ?>
                        <option value="<?= (int) $type['id'] ?>" <?= $value('room_type_id') === (string) $type['id'] ? 'selected' : '' ?>>
                            <?= e((string) $type['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="label-caps mb-2 block text-outline" for="floor">Floor</label>
                <input id="floor" name="floor" class="input-field" value="<?= e($value('floor')) ?>"
                       placeholder="Floor 1 - Garden">
            </div>

            <div>
                <label class="label-caps mb-2 block text-outline" for="status">Status</label>
                <select id="status" name="status" class="input-field" required>
                    <?php foreach ($statuses as $statusOption): ?>
                        <option value="<?= e($statusOption) ?>" <?= $value('status', 'available') === $statusOption ? 'selected' : '' ?>>
                            <?= e($roomService->labelForStatus($statusOption)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="label-caps mb-2 block text-outline" for="notes">Notes</label>
                <textarea id="notes" name="notes" rows="3" class="input-field resize-none"><?= e($value('notes')) ?></textarea>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="btn-action">Save Room</button>
                <a href="<?= e(url('/rooms')) ?>" class="btn-outline">Cancel</a>
            </div>
        </form>
    <?php endif; ?>
</div>
