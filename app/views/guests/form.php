<?php
/** @var array<string, mixed>|null $guest */
/** @var array<string, string> $errors */
/** @var array<string, mixed> $old */
/** @var \App\Services\GuestService $guestService */

$isEdit = $guest !== null;
$action = $isEdit ? url('/guests/' . $guest['id']) : url('/guests');
$value = static function (string $key, mixed $default = '') use ($old, $guest): string {
    if (array_key_exists($key, $old)) {
        return (string) ($old[$key] ?? '');
    }
    if ($guest !== null && array_key_exists($key, $guest) && $guest[$key] !== null) {
        return (string) $guest[$key];
    }

    return (string) $default;
};
?>
<div class="mx-auto max-w-3xl space-y-stack-gap">
    <div>
        <p class="text-body-sm text-on-surface-variant">
            <a href="<?= e(url('/guests')) ?>" class="hover:text-primary">Guests</a>
            / <?= $isEdit ? 'Edit' : 'New' ?>
        </p>
        <h1 class="text-headline-md text-on-surface"><?= e($title ?? ($isEdit ? 'Edit Guest' : 'New Guest')) ?></h1>
    </div>

    <form method="post" action="<?= e($action) ?>" class="surface-card space-y-6 p-6">
        <?= \App\Core\CSRF::field() ?>

        <div>
            <label class="label-caps mb-2 block text-outline" for="full_name">Full name</label>
            <input id="full_name" name="full_name" class="input-field" required maxlength="150"
                   value="<?= e($value('full_name')) ?>" placeholder="Ama Mensah">
            <?php if (!empty($errors['full_name'])): ?>
                <p class="mt-1 text-body-sm text-error"><?= e($errors['full_name']) ?></p>
            <?php endif; ?>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label-caps mb-2 block text-outline" for="phone">Phone</label>
                <input id="phone" name="phone" class="input-field data-mono" maxlength="30"
                       value="<?= e($value('phone')) ?>" placeholder="+233…">
                <?php if (!empty($errors['phone'])): ?>
                    <p class="mt-1 text-body-sm text-error"><?= e($errors['phone']) ?></p>
                <?php endif; ?>
            </div>
            <div>
                <label class="label-caps mb-2 block text-outline" for="email">Email</label>
                <input id="email" name="email" type="email" class="input-field" maxlength="150"
                       value="<?= e($value('email')) ?>" placeholder="guest@example.com">
                <?php if (!empty($errors['email'])): ?>
                    <p class="mt-1 text-body-sm text-error"><?= e($errors['email']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label-caps mb-2 block text-outline" for="id_type">ID type</label>
                <select id="id_type" name="id_type" class="input-field">
                    <option value="">—</option>
                    <?php foreach (\App\Models\Guest::ID_TYPES as $type): ?>
                        <option value="<?= e($type) ?>" <?= $value('id_type') === $type ? 'selected' : '' ?>>
                            <?= e($guestService->labelForIdType($type)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($errors['id_type'])): ?>
                    <p class="mt-1 text-body-sm text-error"><?= e($errors['id_type']) ?></p>
                <?php endif; ?>
            </div>
            <div>
                <label class="label-caps mb-2 block text-outline" for="id_number">ID number</label>
                <input id="id_number" name="id_number" class="input-field data-mono" maxlength="100"
                       value="<?= e($value('id_number')) ?>">
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label-caps mb-2 block text-outline" for="nationality">Nationality</label>
                <input id="nationality" name="nationality" class="input-field" maxlength="80"
                       value="<?= e($value('nationality')) ?>" placeholder="Ghanaian">
            </div>
            <div>
                <label class="label-caps mb-2 block text-outline" for="address">Address</label>
                <input id="address" name="address" class="input-field" maxlength="255"
                       value="<?= e($value('address')) ?>">
            </div>
        </div>

        <div>
            <label class="label-caps mb-2 block text-outline" for="notes">Notes</label>
            <textarea id="notes" name="notes" rows="4" class="input-field resize-none"
                      placeholder="Preferences, VIP flags, special requests…"><?= e($value('notes')) ?></textarea>
        </div>

        <div class="flex flex-wrap gap-2">
            <button type="submit" class="btn-primary"><?= $isEdit ? 'Save changes' : 'Create guest' ?></button>
            <a href="<?= e($isEdit ? url('/guests/' . $guest['id']) : url('/guests')) ?>" class="btn-outline">Cancel</a>
        </div>
    </form>
</div>
