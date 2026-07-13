<?php
/** @var list<array<string, mixed>> $notifications */
/** @var int $unreadCount */
/** @var string|null $filter */
/** @var \App\Services\NotificationService $notificationService */
?>
<div class="space-y-stack-gap">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-headline-md text-on-surface">Notifications</h1>
            <p class="text-body-sm text-on-surface-variant">
                Booking, housekeeping, maintenance, and payment alerts for your account.
            </p>
        </div>
        <?php if ($unreadCount > 0): ?>
            <form method="post" action="<?= e(url('/notifications/read-all')) ?>">
                <?= \App\Core\CSRF::field() ?>
                <button type="submit" class="btn-outline">Mark all read (<?= (int) $unreadCount ?>)</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="flex flex-wrap gap-2">
        <a href="<?= e(url('/notifications')) ?>"
           class="<?= $filter === null ? 'btn-primary' : 'btn-outline' ?>">All</a>
        <a href="<?= e(url('/notifications?filter=unread')) ?>"
           class="<?= $filter === 'unread' ? 'btn-primary' : 'btn-outline' ?>">Unread</a>
        <a href="<?= e(url('/notifications?filter=read')) ?>"
           class="<?= $filter === 'read' ? 'btn-primary' : 'btn-outline' ?>">Read</a>
    </div>

    <div class="surface-card overflow-hidden">
        <?php if ($notifications === []): ?>
            <p class="px-cell-x py-8 text-center text-body-sm text-on-surface-variant">
                No notifications<?= $filter ? ' in this filter' : '' ?>.
            </p>
        <?php else: ?>
            <ul class="divide-y divide-outline-variant">
                <?php foreach ($notifications as $row): ?>
                    <?php
                    $isUnread = !(int) $row['is_read'];
                    $link = $notificationService->linkFor($row);
                    ?>
                    <li class="flex flex-wrap items-start gap-3 px-cell-x py-3 <?= $isUnread ? 'bg-primary/[0.03]' : '' ?>">
                        <div class="mt-1 flex h-8 w-8 shrink-0 items-center justify-center rounded bg-surface-container-high text-primary">
                            <span class="material-symbols-outlined text-[18px]">
                                <?= e(match ((string) ($row['type'] ?? '')) {
                                    'reservation' => 'event_available',
                                    'housekeeping' => 'cleaning_services',
                                    'maintenance' => 'build',
                                    'payment' => 'payments',
                                    default => 'notifications',
                                }) ?>
                            </span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-title-sm text-on-surface"><?= e((string) $row['title']) ?></p>
                                <span class="rounded-sm bg-surface-container-high px-1.5 py-0.5 text-[10px] font-bold uppercase text-on-surface-variant">
                                    <?= e($notificationService->labelForType($row['type'] !== null ? (string) $row['type'] : null)) ?>
                                </span>
                                <?php if ($isUnread): ?>
                                    <span class="rounded-sm bg-secondary-fixed px-1.5 py-0.5 text-[10px] font-bold uppercase text-on-secondary-fixed-variant">New</span>
                                <?php endif; ?>
                            </div>
                            <p class="text-body-sm text-on-surface-variant"><?= e((string) $row['message']) ?></p>
                            <p class="mt-1 data-mono text-[11px] text-outline"><?= e(format_datetime((string) $row['created_at'])) ?></p>
                        </div>
                        <div class="flex shrink-0 flex-wrap gap-1">
                            <?php if ($link !== null): ?>
                                <a class="btn-ghost" href="<?= e(url($link)) ?>">Open</a>
                            <?php endif; ?>
                            <?php if ($isUnread): ?>
                                <form method="post" action="<?= e(url('/notifications/' . $row['id'] . '/read')) ?>">
                                    <?= \App\Core\CSRF::field() ?>
                                    <button type="submit" class="btn-ghost">Mark read</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
