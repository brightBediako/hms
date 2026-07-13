<?php

declare(strict_types=1);

/** @var string|null $flashSuccess */
/** @var string|null $flashError */
?>
<?php if (!empty($flashSuccess)): ?>
    <div class="rounded border border-primary-fixed bg-primary-fixed px-4 py-3 text-body-sm text-on-primary-fixed" role="status">
        <?= e((string) $flashSuccess) ?>
    </div>
<?php endif; ?>

<?php if (!empty($flashError)): ?>
    <div class="rounded border border-error-container bg-error-container px-4 py-3 text-body-sm text-on-error-container" role="alert">
        <?= e((string) $flashError) ?>
    </div>
<?php endif; ?>
