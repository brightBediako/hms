<?php

declare(strict_types=1);

/** @var string $content */
/** @var string|null $title */
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Print') ?> - <?= e(hotel_name()) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff; }
        }
    </style>
</head>
<body class="bg-white text-on-surface">
    <div class="no-print mx-auto max-w-3xl px-6 py-4 flex justify-between gap-2">
        <a href="javascript:history.back()" class="btn-outline">Back</a>
        <button type="button" class="btn-primary" onclick="window.print()">Print</button>
    </div>
    <div class="mx-auto max-w-3xl px-6 py-4">
        <?= $content ?>
    </div>
</body>
</html>
