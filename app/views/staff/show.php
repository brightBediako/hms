<?php
/** @var array<string, mixed> $member */
/** @var list<array{key: string, description: ?string}> $permissions */
/** @var \App\Services\StaffService $staffService */
/** @var bool $isSelf */

$chip = $staffService->statusChipClasses((string) $member['status']);
?>
<div class="space-y-stack-gap">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-body-sm text-on-surface-variant">
                <a href="<?= e(url('/staff')) ?>" class="hover:text-primary">Staff</a> / Profile
            </p>
            <h1 class="text-headline-md text-on-surface"><?= e((string) $member['full_name']) ?></h1>
            <div class="mt-2 flex flex-wrap gap-2">
                <span class="inline-block rounded-sm px-2 py-0.5 text-[10px] font-bold uppercase <?= e($chip['bg']) ?> <?= e($chip['text']) ?>">
                    <?= e($staffService->labelForStatus((string) $member['status'])) ?>
                </span>
                <?php if ($isSelf): ?>
                    <span class="inline-block rounded-sm bg-surface-container-high px-2 py-0.5 text-[10px] font-bold uppercase text-on-surface-variant">
                        You
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <a href="<?= e(url('/staff/' . $member['id'] . '/edit')) ?>" class="btn-primary">Edit</a>
    </div>

    <div class="grid gap-stack-gap lg:grid-cols-2">
        <section class="surface-card space-y-4 p-6">
            <h2 class="text-title-sm text-on-surface">Account</h2>
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <p class="label-caps mb-1 text-outline">Email</p>
                    <p class="text-body-sm"><?= e((string) $member['email']) ?></p>
                </div>
                <div>
                    <p class="label-caps mb-1 text-outline">Phone</p>
                    <p class="data-mono text-body-sm"><?= e((string) ($member['phone'] ?: '—')) ?></p>
                </div>
                <div>
                    <p class="label-caps mb-1 text-outline">Role</p>
                    <p class="text-body-sm font-semibold"><?= e((string) $member['role_name']) ?></p>
                    <?php if (!empty($member['role_description'])): ?>
                        <p class="text-[11px] text-on-surface-variant"><?= e((string) $member['role_description']) ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <p class="label-caps mb-1 text-outline">Last login</p>
                    <p class="data-mono text-body-sm">
                        <?= e($member['last_login_at'] ? format_datetime((string) $member['last_login_at']) : 'Never') ?>
                    </p>
                </div>
            </div>
        </section>

        <section class="surface-card space-y-3 p-6">
            <h2 class="text-title-sm text-on-surface">Permissions for this role</h2>
            <p class="text-body-sm text-on-surface-variant">
                Read-only from seeded <span class="data-mono">role_permissions</span>. Change the role to adjust access.
            </p>
            <?php if ($permissions === []): ?>
                <p class="text-body-sm text-on-surface-variant">No permissions assigned to this role.</p>
            <?php else: ?>
                <ul class="max-h-80 space-y-1 overflow-y-auto text-body-sm">
                    <?php foreach ($permissions as $perm): ?>
                        <li class="flex gap-2 border-t border-outline-variant py-1.5 first:border-t-0">
                            <span class="data-mono text-primary-container"><?= e((string) $perm['key']) ?></span>
                            <?php if (!empty($perm['description'])): ?>
                                <span class="text-on-surface-variant">— <?= e((string) $perm['description']) ?></span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>
</div>
