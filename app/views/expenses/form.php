<?php
/** @var list<array<string, mixed>> $categories */
/** @var array<string, string> $errors */
/** @var array<string, mixed> $old */

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
            <a href="<?= e(url('/expenses')) ?>" class="hover:text-primary">Expenses</a> / New
        </p>
        <h1 class="text-headline-md text-on-surface">Record expense</h1>
    </div>

    <form method="post" action="<?= e(url('/expenses')) ?>" enctype="multipart/form-data" class="surface-card space-y-4 p-6">
        <?= \App\Core\CSRF::field() ?>

        <div>
            <label class="label-caps mb-2 block text-outline" for="category_id">Category</label>
            <select id="category_id" name="category_id" class="input-field" required>
                <option value="">Select…</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int) $cat['id'] ?>" <?= $value('category_id') === (string) $cat['id'] ? 'selected' : '' ?>>
                        <?= e((string) $cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($errors['category_id'])): ?>
                <p class="mt-1 text-body-sm text-error"><?= e($errors['category_id']) ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label class="label-caps mb-2 block text-outline" for="description">Description</label>
            <input id="description" name="description" class="input-field" required maxlength="255"
                   value="<?= e($value('description')) ?>" placeholder="Electricity bill — June">
            <?php if (!empty($errors['description'])): ?>
                <p class="mt-1 text-body-sm text-error"><?= e($errors['description']) ?></p>
            <?php endif; ?>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label-caps mb-2 block text-outline" for="amount">Amount</label>
                <input id="amount" name="amount" type="number" step="0.01" min="0.01" class="input-field data-mono" required
                       value="<?= e($value('amount')) ?>">
                <?php if (!empty($errors['amount'])): ?>
                    <p class="mt-1 text-body-sm text-error"><?= e($errors['amount']) ?></p>
                <?php endif; ?>
            </div>
            <div>
                <label class="label-caps mb-2 block text-outline" for="expense_date">Date</label>
                <input id="expense_date" name="expense_date" type="date" class="input-field" required
                       value="<?= e($value('expense_date', date('Y-m-d'))) ?>">
                <?php if (!empty($errors['expense_date'])): ?>
                    <p class="mt-1 text-body-sm text-error"><?= e($errors['expense_date']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div>
            <label class="label-caps mb-2 block text-outline" for="receipt">Receipt (optional)</label>
            <input id="receipt" name="receipt" type="file" class="input-field" accept=".pdf,.jpg,.jpeg,.png,.webp">
            <p class="mt-1 text-[11px] text-on-surface-variant">PDF or image, max 5 MB.</p>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="btn-action">Save expense</button>
            <a href="<?= e(url('/expenses')) ?>" class="btn-outline">Cancel</a>
        </div>
    </form>

    <form method="post" action="<?= e(url('/expenses/categories')) ?>" class="surface-card space-y-3 p-6">
        <?= \App\Core\CSRF::field() ?>
        <h2 class="text-title-sm text-on-surface">Add category</h2>
        <p class="text-body-sm text-on-surface-variant">Seeded: Utilities, Repairs, Purchases, Operational.</p>
        <div class="flex flex-wrap gap-2">
            <input name="name" class="input-field flex-1" maxlength="80" placeholder="New category name" required>
            <button type="submit" class="btn-outline">Add</button>
        </div>
    </form>
</div>
