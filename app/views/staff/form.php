<?php
/** @var array<string, mixed>|null $member */
/** @var list<array<string, mixed>> $roles */
/** @var array<string, string> $errors */
/** @var array<string, mixed> $old */
/** @var \App\Services\StaffService $staffService */

$isEdit = $member !== null;
$action = $isEdit ? url('/staff/' . $member['id']) : url('/staff');
$value = static function (string $key, mixed $default = '') use ($old, $member): string {
    if (array_key_exists($key, $old) && $old[$key] !== null) {
        return (string) $old[$key];
    }
    if ($member !== null && array_key_exists($key, $member) && $member[$key] !== null) {
        return (string) $member[$key];
    }

    return (string) $default;
};
?>
<div class="mx-auto max-w-xl space-y-stack-gap">
    <div>
        <p class="text-body-sm text-on-surface-variant">
            <a href="<?= e(url('/staff')) ?>" class="hover:text-primary">Staff</a>
            / <?= $isEdit ? 'Edit' : 'New' ?>
        </p>
        <h1 class="text-headline-md text-on-surface"><?= e($title ?? ($isEdit ? 'Edit staff' : 'New staff')) ?></h1>
    </div>

    <form method="post" action="<?= e($action) ?>" class="surface-card space-y-4 p-6">
        <?= \App\Core\CSRF::field() ?>

        <div>
            <label class="label-caps mb-2 block text-outline" for="full_name">Full name</label>
            <input id="full_name" name="full_name" class="input-field" required maxlength="100"
                   value="<?= e($value('full_name')) ?>">
            <?php if (!empty($errors['full_name'])): ?>
                <p class="mt-1 text-body-sm text-error"><?= e($errors['full_name']) ?></p>
            <?php endif; ?>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label-caps mb-2 block text-outline" for="email">Email</label>
                <input id="email" name="email" type="email" class="input-field" required maxlength="150"
                       value="<?= e($value('email')) ?>">
                <?php if (!empty($errors['email'])): ?>
                    <p class="mt-1 text-body-sm text-error"><?= e($errors['email']) ?></p>
                <?php endif; ?>
            </div>
            <div>
                <label class="label-caps mb-2 block text-outline" for="phone">Phone</label>
                <input id="phone" name="phone" class="input-field data-mono" maxlength="30"
                       value="<?= e($value('phone')) ?>">
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label-caps mb-2 block text-outline" for="role_id">Role</label>
                <select id="role_id" name="role_id" class="input-field" required>
                    <option value="">Select…</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= (int) $role['id'] ?>" <?= $value('role_id') === (string) $role['id'] ? 'selected' : '' ?>>
                            <?= e((string) $role['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($errors['role_id'])): ?>
                    <p class="mt-1 text-body-sm text-error"><?= e($errors['role_id']) ?></p>
                <?php endif; ?>
            </div>
            <div>
                <label class="label-caps mb-2 block text-outline" for="status">Status</label>
                <select id="status" name="status" class="input-field" required>
                    <?php foreach (\App\Models\Staff::STATUSES as $status): ?>
                        <option value="<?= e($status) ?>" <?= $value('status', 'active') === $status ? 'selected' : '' ?>>
                            <?= e($staffService->labelForStatus($status)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div>
            <label class="label-caps mb-2 block text-outline" for="password">
                <?= $isEdit ? 'New password (optional)' : 'Password' ?>
            </label>
            <input id="password" name="password" type="password" class="input-field"
                   <?= $isEdit ? '' : 'required' ?> minlength="8" maxlength="100"
                   autocomplete="<?= $isEdit ? 'new-password' : 'new-password' ?>">
            <p class="mt-1 text-[11px] text-on-surface-variant">
                <?= $isEdit ? 'Leave blank to keep the current password.' : 'At least 8 characters.' ?>
            </p>
            <?php if (!empty($errors['password'])): ?>
                <p class="mt-1 text-body-sm text-error"><?= e($errors['password']) ?></p>
            <?php endif; ?>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="btn-action"><?= $isEdit ? 'Save changes' : 'Create account' ?></button>
            <a href="<?= e($isEdit ? url('/staff/' . $member['id']) : url('/staff')) ?>" class="btn-outline">Cancel</a>
        </div>
    </form>
</div>
