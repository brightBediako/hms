<?php

declare(strict_types=1);

/**
 * Near-square status chip — Hospitality Command (ui-tokens / ui-rules).
 *
 * @var string $status  Schema value e.g. available
 * @var \App\Services\RoomService $roomService
 */
$label = $roomService->labelForStatus($status);
$chip = $roomService->chipClasses($status);
?>
<span class="inline-block rounded-sm px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider <?= e($chip['bg']) ?> <?= e($chip['text']) ?>">
    <?= e($label) ?>
</span>
