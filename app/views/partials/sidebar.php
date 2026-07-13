<?php

declare(strict_types=1);

use App\Core\Auth;

/** @var array{operations: list<array<string, mixed>>, administration: list<array<string, mixed>>} $nav */
$nav = require HMS_ROOT . '/config/navigation.php';
$canCreateReservation = Auth::can(\Permission::RESERVATIONS_CREATE);

$renderItem = static function (array $item): void {
    if (!Auth::can((string) $item['permission'])) {
        return;
    }

    $active = nav_is_active((string) $item['path']);
    $ready = !empty($item['ready']);
    $class = $active ? 'nav-link-active' : 'nav-link';
    $icon = e((string) $item['icon']);
    $label = e((string) $item['label']);

    if ($ready) {
        echo '<a class="' . $class . '" href="' . e(url((string) $item['path'])) . '">';
        echo '<span class="material-symbols-outlined mr-3">' . $icon . '</span>';
        echo '<span>' . $label . '</span>';
        echo '</a>';
        return;
    }

    echo '<span class="' . $class . ' opacity-60 cursor-not-allowed" title="Coming in a later feature" aria-disabled="true">';
    echo '<span class="material-symbols-outlined mr-3">' . $icon . '</span>';
    echo '<span>' . $label . '</span>';
    echo '</span>';
};
?>
<aside id="app-sidebar"
       class="fixed inset-y-0 left-0 z-40 flex w-sidebar -translate-x-full flex-col border-r border-outline-variant bg-surface-container-high py-4 transition-transform duration-200 lg:translate-x-0">
    <div class="mb-8 flex items-start justify-between px-6">
        <div>
            <h1 class="text-headline-md text-primary"><?= e(hotel_name()) ?></h1>
            <p class="text-body-sm text-on-surface-variant">Property Management</p>
        </div>
        <button type="button" id="sidebar-close" class="lg:hidden btn-ghost !px-2" aria-label="Close menu">
            <span class="material-symbols-outlined">close</span>
        </button>
    </div>

    <nav class="custom-scrollbar flex-1 space-y-1 overflow-y-auto px-3">
        <?php foreach ($nav['operations'] as $item): ?>
            <?php $renderItem($item); ?>
        <?php endforeach; ?>

        <?php
        $adminVisible = false;
        foreach ($nav['administration'] as $item) {
            if (Auth::can((string) $item['permission'])) {
                $adminVisible = true;
                break;
            }
        }
        ?>
        <?php if ($adminVisible): ?>
            <div class="px-3 pb-2 pt-4">
                <p class="label-caps text-outline">Administration</p>
            </div>
            <?php foreach ($nav['administration'] as $item): ?>
                <?php $renderItem($item); ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </nav>

    <?php if ($canCreateReservation): ?>
        <div class="mt-auto px-3 pt-4">
            <button type="button"
                    class="btn-primary w-full opacity-70"
                    title="New Reservation — Reservations module coming soon"
                    disabled>
                <span class="material-symbols-outlined text-[18px]">add</span>
                New Reservation
            </button>
        </div>
    <?php endif; ?>
</aside>
