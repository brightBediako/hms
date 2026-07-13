<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\CSRF;

/** @var string $pageTitle */
/** @var array<string, mixed>|null $user */

$canCheckIn = Auth::can(\Permission::FRONTDESK_CHECKIN);
$canCreateReservation = Auth::can(\Permission::RESERVATIONS_CREATE);
?>
<header class="fixed right-0 top-0 z-20 flex h-topbar items-center justify-between border-b border-outline-variant bg-surface px-4 lg:left-sidebar lg:px-6">
    <div class="flex min-w-0 flex-1 items-center gap-3">
        <button type="button" id="sidebar-open" class="btn-ghost !px-2 lg:hidden" aria-label="Open menu">
            <span class="material-symbols-outlined">menu</span>
        </button>
        <div class="min-w-0">
            <p class="truncate text-title-sm text-primary"><?= e(hotel_name()) ?></p>
            <p class="hidden text-body-sm text-on-surface-variant sm:block"><?= e($pageTitle) ?></p>
        </div>
        <div class="relative ml-2 hidden max-w-md flex-1 md:block">
            <span class="material-symbols-outlined pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[18px]">search</span>
            <input type="search"
                   class="input-field !border-0 bg-surface-container-low py-1.5 pl-10 pr-4"
                   placeholder="Search guests, rooms, bookings..."
                   disabled
                   title="Search coming with module features"
                   aria-label="Search">
        </div>
    </div>

    <div class="flex shrink-0 items-center gap-2 sm:gap-4">
        <div class="hidden items-center gap-2 sm:flex">
            <?php if ($canCheckIn): ?>
                <a href="<?= e(url('/frontdesk')) ?>"
                   class="btn-outline pointer-events-none opacity-70"
                   title="Check-In — Front Desk module coming soon"
                   aria-disabled="true">Check In</a>
            <?php endif; ?>
            <?php if ($canCreateReservation): ?>
                <a href="<?= e(url('/reservations')) ?>"
                   class="btn-action pointer-events-none opacity-70"
                   title="New Reservation — Reservations module coming soon"
                   aria-disabled="true">New Reservation</a>
            <?php endif; ?>
        </div>

        <div class="hidden h-6 w-px bg-outline-variant sm:block"></div>

        <div class="flex items-center gap-3">
            <div class="hidden text-right sm:block">
                <p class="text-body-sm font-semibold text-on-surface"><?= e((string) ($user['full_name'] ?? '')) ?></p>
                <p class="text-body-sm text-on-surface-variant"><?= e((string) ($user['role_name'] ?? '')) ?></p>
            </div>
            <div class="flex h-9 w-9 items-center justify-center rounded bg-primary-container text-body-sm font-bold text-on-primary-container"
                 aria-hidden="true">
                <?php
                $name = (string) ($user['full_name'] ?? 'S');
                $parts = preg_split('/\s+/', trim($name)) ?: [];
                $initials = strtoupper(substr($parts[0] ?? 'S', 0, 1) . substr($parts[1] ?? '', 0, 1));
                echo e($initials);
                ?>
            </div>
            <form method="post" action="<?= e(url('/logout')) ?>">
                <?= CSRF::field() ?>
                <button type="submit" class="btn-ghost !px-2" title="Sign out" aria-label="Sign out">
                    <span class="material-symbols-outlined">logout</span>
                </button>
            </form>
        </div>
    </div>
</header>
