<?php
/** @var bool $canSettings */
/** @var bool $canBackup */
/** @var array<string, string>|null $preview */
?>
<div class="space-y-stack-gap">
    <div>
        <h1 class="text-headline-md text-on-surface">Settings</h1>
        <p class="text-body-sm text-on-surface-variant">
            Hotel configuration and database backups.
        </p>
    </div>

    <?php if ($canSettings && $preview !== null): ?>
        <div class="surface-card grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <p class="label-caps text-outline">Hotel</p>
                <p class="text-body-sm font-semibold"><?= e($preview['hotel_name']) ?></p>
            </div>
            <div>
                <p class="label-caps text-outline">Currency</p>
                <p class="data-mono text-body-sm"><?= e($preview['currency']) ?></p>
            </div>
            <div>
                <p class="label-caps text-outline">Taxes</p>
                <p class="data-mono text-body-sm"><?= e($preview['tax_lines_label'] ?? '') ?></p>
            </div>
            <div>
                <p class="label-caps text-outline">Check-out</p>
                <p class="data-mono text-body-sm"><?= e($preview['check_out_time']) ?></p>
            </div>
        </div>
    <?php endif; ?>

    <div class="grid gap-3 sm:grid-cols-2">
        <?php if ($canSettings): ?>
            <a href="<?= e(url('/settings/hotel')) ?>" class="surface-card flex items-start gap-3 p-4 hover:border-primary">
                <span class="material-symbols-outlined text-primary">tune</span>
                <div>
                    <p class="text-title-sm">Hotel settings</p>
                    <p class="text-body-sm text-on-surface-variant">
                        Name, currency, taxes (GETF / NHIL / VAT), checkout time.
                    </p>
                </div>
            </a>
        <?php endif; ?>

        <?php if ($canBackup): ?>
            <a href="<?= e(url('/settings/backups')) ?>" class="surface-card flex items-start gap-3 p-4 hover:border-primary">
                <span class="material-symbols-outlined text-primary">cloud_download</span>
                <div>
                    <p class="text-title-sm">Backups</p>
                    <p class="text-body-sm text-on-surface-variant">Create, download, and rotate MySQL dumps.</p>
                </div>
            </a>
        <?php endif; ?>
    </div>
</div>
