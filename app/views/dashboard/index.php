<?php
/** @var array<string, mixed>|null $user */
/** @var list<string> $permissions */
/** @var bool $canDashboard */
?>
<div class="space-y-stack-gap">
    <div>
        <h1 class="text-headline-md text-primary">Dashboard</h1>
        <p class="text-body-sm text-on-surface-variant">
            Authenticated shell is live. Module metrics arrive with Reports &amp; Dashboard (feature 16).
        </p>
    </div>

    <div class="grid gap-stack-gap md:grid-cols-3">
        <div class="surface-card p-4">
            <p class="label-caps text-outline">Signed in</p>
            <p class="mt-2 text-title-sm text-on-surface"><?= e((string) ($user['full_name'] ?? '')) ?></p>
            <p class="text-body-sm text-on-surface-variant"><?= e((string) ($user['email'] ?? '')) ?></p>
        </div>
        <div class="surface-card p-4">
            <p class="label-caps text-outline">Role</p>
            <p class="mt-2 text-title-sm text-on-surface"><?= e((string) ($user['role_name'] ?? '')) ?></p>
            <p class="text-body-sm text-on-surface-variant">
                <span class="data-mono">dashboard.view</span>:
                <?= $canDashboard ? 'granted' : 'denied' ?>
            </p>
        </div>
        <div class="surface-card p-4">
            <p class="label-caps text-outline">Permissions</p>
            <p class="mt-2 text-headline-md text-primary"><?= count($permissions) ?></p>
            <p class="text-body-sm text-on-surface-variant">Loaded from database at login</p>
        </div>
    </div>

    <div class="surface-card p-4">
        <p class="label-caps mb-3 text-outline">Permission keys</p>
        <ul class="grid gap-1 sm:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($permissions as $key): ?>
                <li class="data-mono text-on-surface-variant"><?= e($key) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
