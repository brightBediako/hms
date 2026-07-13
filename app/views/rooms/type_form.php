<?php
/** @var array<string, mixed>|null $type */
/** @var list<string> $amenitiesSelected */
/** @var list<string> $amenityOptions */
/** @var array<string, string> $errors */
/** @var array<string, mixed> $old */

$isEdit = $type !== null;
$action = $isEdit ? url('/rooms/types/' . $type['id']) : url('/rooms/types');
$value = static function (string $key, mixed $default = '') use ($old, $type): string {
    if (array_key_exists($key, $old)) {
        return (string) $old[$key];
    }
    if ($type !== null && array_key_exists($key, $type) && $type[$key] !== null) {
        return (string) $type[$key];
    }

    return (string) $default;
};

if (isset($old['amenities']) && is_array($old['amenities'])) {
    /** @var list<string> $amenitiesSelected */
    $amenitiesSelected = array_values(array_filter($old['amenities'], 'is_string'));
}
?>
<div class="mx-auto max-w-3xl space-y-stack-gap">
    <div class="flex items-center justify-between gap-3">
        <div>
            <p class="text-body-sm text-on-surface-variant">
                <a href="<?= e(url('/rooms/types')) ?>" class="hover:text-primary">Room Types</a>
                / <?= $isEdit ? 'Edit' : 'New' ?>
            </p>
            <h1 class="text-headline-md text-on-surface"><?= e($title ?? ($isEdit ? 'Edit Room Type' : 'New Room Type')) ?></h1>
        </div>
    </div>

    <form method="post" action="<?= e($action) ?>" class="surface-card space-y-6 p-6">
        <?= \App\Core\CSRF::field() ?>

        <div>
            <label for="name" class="label-caps mb-2 block text-outline">Room type name</label>
            <input id="name" name="name" class="input-field" required maxlength="80"
                   value="<?= e($value('name')) ?>" placeholder="e.g. Deluxe King">
            <?php if (!empty($errors['name'])): ?>
                <p class="mt-1 text-body-sm text-error"><?= e($errors['name']) ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label for="description" class="label-caps mb-2 block text-outline">Description</label>
            <textarea id="description" name="description" rows="3" class="input-field resize-none"
                      placeholder="Short description for front desk and booking screens"><?= e($value('description')) ?></textarea>
        </div>

        <div>
            <label class="label-caps mb-2 block text-outline">Capacity &amp; setup</label>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <p class="mb-1 text-[10px] text-outline">MAX ADULTS</p>
                    <input type="number" min="1" max="20" name="base_capacity_adults" class="input-field"
                           value="<?= e($value('base_capacity_adults', '2')) ?>" required>
                </div>
                <div>
                    <p class="mb-1 text-[10px] text-outline">MAX CHILDREN</p>
                    <input type="number" min="0" max="20" name="base_capacity_children" class="input-field"
                           value="<?= e($value('base_capacity_children', '0')) ?>" required>
                </div>
            </div>
        </div>

        <div>
            <label class="label-caps mb-2 block text-outline">Pricing</label>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <p class="mb-1 text-[10px] text-outline">BASE NIGHTLY RATE (GHS)</p>
                    <input type="number" step="0.01" min="0" name="base_rate" class="input-field data-mono"
                           value="<?= e($value('base_rate')) ?>" required>
                    <?php if (!empty($errors['base_rate'])): ?>
                        <p class="mt-1 text-body-sm text-error"><?= e($errors['base_rate']) ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <p class="mb-1 text-[10px] text-outline">EXTRA BED RATE (GHS)</p>
                    <input type="number" step="0.01" min="0" name="extra_bed_rate" class="input-field data-mono"
                           value="<?= e($value('extra_bed_rate')) ?>">
                    <?php if (!empty($errors['extra_bed_rate'])): ?>
                        <p class="mt-1 text-body-sm text-error"><?= e($errors['extra_bed_rate']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div>
            <label class="label-caps mb-2 block text-outline">Amenities</label>
            <div class="grid gap-2 sm:grid-cols-2">
                <?php foreach ($amenityOptions as $amenity): ?>
                    <label class="flex cursor-pointer items-center gap-3 rounded border border-outline-variant p-2 hover:bg-surface-container-low">
                        <input type="checkbox" name="amenities[]" value="<?= e($amenity) ?>"
                               class="h-4 w-4 rounded border-outline-variant text-primary focus:ring-primary"
                            <?= in_array($amenity, $amenitiesSelected, true) ? 'checked' : '' ?>>
                        <span class="text-body-sm text-on-surface"><?= e($amenity) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="flex flex-wrap gap-2 border-t border-outline-variant pt-4">
            <button type="submit" class="btn-action"><?= $isEdit ? 'Save Changes' : 'Create Room Type' ?></button>
            <a href="<?= e($isEdit ? url('/rooms/types/' . $type['id']) : url('/rooms/types')) ?>" class="btn-outline">Cancel</a>
        </div>
    </form>
</div>
