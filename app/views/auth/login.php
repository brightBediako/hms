<?php
/** @var string|null $error */
/** @var array<string, string> $errors */
/** @var string $email */
?>
<div class="surface-card w-full max-w-md p-7">
    <h1 class="text-headline-md text-primary">Sign in</h1>
    <p class="mb-5 mt-1 text-body-sm text-on-surface-variant"><?= e(hotel_name()) ?></p>

    <?php if (!empty($error)): ?>
        <div class="mb-4 rounded bg-error-container px-3 py-2 text-body-sm text-on-error-container"><?= e((string) $error) ?></div>
    <?php endif; ?>

    <?php $success = \App\Core\Session::pullFlash('success'); ?>
    <?php if (!empty($success)): ?>
        <div class="mb-4 rounded bg-primary-fixed px-3 py-2 text-body-sm text-on-primary-fixed"><?= e((string) $success) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= e(url('/login')) ?>" novalidate class="space-y-4">
        <?= \App\Core\CSRF::field() ?>

        <div>
            <label for="email" class="label-caps mb-1 block text-on-surface-variant">Email</label>
            <input id="email" class="input-field" type="email" name="email" value="<?= e((string) $email) ?>" autocomplete="username" required>
            <?php if (!empty($errors['email'])): ?>
                <p class="mt-1 text-body-sm text-error"><?= e($errors['email']) ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label for="password" class="label-caps mb-1 block text-on-surface-variant">Password</label>
            <input id="password" class="input-field" type="password" name="password" autocomplete="current-password" required>
            <?php if (!empty($errors['password'])): ?>
                <p class="mt-1 text-body-sm text-error"><?= e($errors['password']) ?></p>
            <?php endif; ?>
        </div>

        <button class="btn-primary w-full" type="submit">Sign in</button>
    </form>

    <p class="mt-5 text-body-sm text-on-surface-variant">Staff accounts only.</p>
</div>
