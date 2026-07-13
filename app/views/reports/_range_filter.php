<?php
/**
 * Shared date-range filter for report screens.
 * @var string $from
 * @var string $to
 * @var string $actionPath  e.g. /reports/revenue
 * @var bool $printMode
 */
if (!empty($printMode)) {
    return;
}
?>
<form method="get" action="<?= e(url($actionPath)) ?>" class="flex flex-wrap items-end gap-3">
    <div>
        <label class="label-caps mb-1 block text-outline" for="from">From</label>
        <input id="from" name="from" type="date" class="input-field" value="<?= e($from) ?>" required>
    </div>
    <div>
        <label class="label-caps mb-1 block text-outline" for="to">To</label>
        <input id="to" name="to" type="date" class="input-field" value="<?= e($to) ?>" required>
    </div>
    <button type="submit" class="btn-primary">Apply</button>
    <a class="btn-outline" href="<?= e(url($actionPath . '?from=' . urlencode($from) . '&to=' . urlencode($to) . '&print=1')) ?>" target="_blank" rel="noopener">
        Print
    </a>
</form>
