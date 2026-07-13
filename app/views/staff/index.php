<?php
/** @var list<array<string, mixed>> $staffList */
/** @var list<array<string, mixed>> $roles */
/** @var array{status:?string,role_id:?int,q:?string} $filters */
/** @var \App\Services\StaffService $staffService */
?>
<div class="space-y-stack-gap">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-headline-md text-on-surface">Staff</h1>
            <p class="text-body-sm text-on-surface-variant">
                Accounts and roles. Permissions come from seeded role assignments.
            </p>
        </div>
        <a href="<?= e(url('/staff/create')) ?>" class="btn-action">
            <span class="material-symbols-outlined text-[18px]">person_add</span>
            New staff
        </a>
    </div>

    <form method="get" action="<?= e(url('/staff')) ?>" class="flex flex-wrap items-end gap-3">
        <div class="min-w-[180px] flex-1">
            <label class="label-caps mb-1 block text-outline" for="q">Search</label>
            <input id="q" name="q" type="search" class="input-field" placeholder="Name, email, phone…"
                   value="<?= e((string) ($filters['q'] ?? '')) ?>">
        </div>
        <div>
            <label class="label-caps mb-1 block text-outline" for="role_id">Role</label>
            <select id="role_id" name="role_id" class="input-field">
                <option value="">All</option>
                <?php foreach ($roles as $role): ?>
                    <option value="<?= (int) $role['id'] ?>" <?= (int) ($filters['role_id'] ?? 0) === (int) $role['id'] ? 'selected' : '' ?>>
                        <?= e((string) $role['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="label-caps mb-1 block text-outline" for="status">Status</label>
            <select id="status" name="status" class="input-field">
                <option value="">All</option>
                <?php foreach (\App\Models\Staff::STATUSES as $status): ?>
                    <option value="<?= e($status) ?>" <?= ($filters['status'] ?? '') === $status ? 'selected' : '' ?>>
                        <?= e($staffService->labelForStatus($status)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn-primary">Filter</button>
    </form>

    <div class="surface-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-surface-container-low">
                    <tr>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Name</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Email</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Role</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Status</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Last login</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($staffList === []): ?>
                        <tr>
                            <td colspan="6" class="px-cell-x py-8 text-center text-body-sm text-on-surface-variant">
                                No staff match these filters.
                                <a class="font-semibold text-primary-container" href="<?= e(url('/staff/create')) ?>">Add staff</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($staffList as $row): ?>
                        <?php $chip = $staffService->statusChipClasses((string) $row['status']); ?>
                        <tr class="border-t border-outline-variant hover:bg-primary/[0.02]">
                            <td class="px-cell-x py-cell-y">
                                <a href="<?= e(url('/staff/' . $row['id'])) ?>" class="text-title-sm hover:text-primary">
                                    <?= e((string) $row['full_name']) ?>
                                </a>
                                <?php if (!empty($row['phone'])): ?>
                                    <p class="data-mono text-[11px] text-on-surface-variant"><?= e((string) $row['phone']) ?></p>
                                <?php endif; ?>
                            </td>
                            <td class="px-cell-x py-cell-y text-body-sm"><?= e((string) $row['email']) ?></td>
                            <td class="px-cell-x py-cell-y text-body-sm"><?= e((string) $row['role_name']) ?></td>
                            <td class="px-cell-x py-cell-y">
                                <span class="inline-block rounded-sm px-2 py-0.5 text-[10px] font-bold uppercase <?= e($chip['bg']) ?> <?= e($chip['text']) ?>">
                                    <?= e($staffService->labelForStatus((string) $row['status'])) ?>
                                </span>
                            </td>
                            <td class="px-cell-x py-cell-y data-mono text-[11px] text-on-surface-variant">
                                <?= e($row['last_login_at'] ? format_datetime((string) $row['last_login_at']) : 'Never') ?>
                            </td>
                            <td class="px-cell-x py-cell-y text-right">
                                <a class="btn-ghost" href="<?= e(url('/staff/' . $row['id'] . '/edit')) ?>">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
