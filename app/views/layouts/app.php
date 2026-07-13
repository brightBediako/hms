<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Session;

/** @var string $content */
/** @var string|null $title */

$user = Auth::user();
$flashSuccess = Session::pullFlash('success');
$flashError = Session::pullFlash('error');
$pageTitle = $title ?? 'Dashboard';
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> - <?= e(hotel_name()) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="bg-background text-on-background">
<div class="min-h-screen">
    <?php require HMS_ROOT . '/app/views/partials/sidebar.php'; ?>

    <div class="lg:pl-sidebar min-h-screen flex flex-col">
        <?php require HMS_ROOT . '/app/views/partials/topbar.php'; ?>

        <main class="flex-1 pt-topbar">
            <div class="p-container-margin space-y-stack-gap">
                <?php require HMS_ROOT . '/app/views/partials/flash.php'; ?>
                <?= $content ?>
            </div>
        </main>
    </div>
</div>

<div id="sidebar-backdrop" class="fixed inset-0 z-30 bg-inverse-surface/40 hidden lg:hidden" hidden></div>

<script src="<?= e(asset('js/shell.js')) ?>" defer></script>
</body>
</html>
