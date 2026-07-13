<?php
/** @var list<array<string, mixed>> $guests */
/** @var string $q */
/** @var bool $canManage */
/** @var \App\Services\GuestService $guestService */
?>
<div class="space-y-stack-gap">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-headline-md text-on-surface">Guests</h1>
            <p class="text-body-sm text-on-surface-variant">
                Profiles used for reservations, front desk, and billing.
            </p>
        </div>
        <?php if ($canManage): ?>
            <a href="<?= e(url('/guests/create')) ?>" class="btn-action">
                <span class="material-symbols-outlined text-[18px]">person_add</span>
                New Guest
            </a>
        <?php endif; ?>
    </div>

    <form method="get" action="<?= e(url('/guests')) ?>" class="flex flex-wrap items-end gap-3">
        <div class="min-w-[220px] flex-1">
            <label class="label-caps mb-1 block text-outline" for="q">Search</label>
            <input id="q" type="search" name="q" class="input-field" placeholder="Name, phone, email, or ID number"
                   value="<?= e($q) ?>">
        </div>
        <button type="submit" class="btn-primary">Search</button>
        <?php if ($q !== ''): ?>
            <a href="<?= e(url('/guests')) ?>" class="btn-outline">Clear</a>
        <?php endif; ?>
    </form>

    <div class="surface-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-surface-container-low">
                    <tr>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Guest</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Phone</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Email</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">ID</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Nationality</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Stays</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($guests === []): ?>
                        <tr>
                            <td colspan="7" class="px-cell-x py-8 text-center text-body-sm text-on-surface-variant">
                                <?php if ($q !== ''): ?>
                                    No guests match “<?= e($q) ?>”.
                                <?php else: ?>
                                    No guest profiles yet.
                                    <?php if ($canManage): ?>
                                        <a class="font-semibold text-primary-container" href="<?= e(url('/guests/create')) ?>">Add the first guest</a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($guests as $guest): ?>
                        <tr class="border-t border-outline-variant hover:bg-primary/[0.02]">
                            <td class="px-cell-x py-cell-y">
                                <a href="<?= e(url('/guests/' . $guest['id'])) ?>" class="text-title-sm text-on-surface hover:text-primary">
                                    <?= e((string) $guest['full_name']) ?>
                                </a>
                            </td>
                            <td class="px-cell-x py-cell-y data-mono text-body-sm text-on-surface-variant">
                                <?= e((string) ($guest['phone'] ?: '—')) ?>
                            </td>
                            <td class="px-cell-x py-cell-y text-body-sm text-on-surface-variant">
                                <?= e((string) ($guest['email'] ?: '—')) ?>
                            </td>
                            <td class="px-cell-x py-cell-y text-body-sm text-on-surface-variant">
                                <?php if (!empty($guest['id_type']) || !empty($guest['id_number'])): ?>
                                    <span class="label-caps text-[10px] text-outline"><?= e($guestService->labelForIdType($guest['id_type'] ?? null)) ?></span>
                                    <span class="data-mono block"><?= e((string) ($guest['id_number'] ?: '—')) ?></span>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td class="px-cell-x py-cell-y text-body-sm text-on-surface-variant">
                                <?= e((string) ($guest['nationality'] ?: '—')) ?>
                            </td>
                            <td class="px-cell-x py-cell-y data-mono text-on-surface-variant">
                                <?= (int) ($guest['stay_count'] ?? 0) ?>
                            </td>
                            <td class="px-cell-x py-cell-y text-right">
                                <a class="btn-ghost" href="<?= e(url('/guests/' . $guest['id'])) ?>">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
