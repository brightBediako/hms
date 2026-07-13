<?php
/** @var array<string, mixed> $expense */
/** @var bool $canManage */
?>
<div class="space-y-stack-gap">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-body-sm text-on-surface-variant">
                <a href="<?= e(url('/expenses')) ?>" class="hover:text-primary">Expenses</a> / Detail
            </p>
            <h1 class="text-headline-md text-on-surface"><?= e((string) $expense['description']) ?></h1>
        </div>
        <?php if ($canManage): ?>
            <form method="post" action="<?= e(url('/expenses/' . $expense['id'] . '/delete')) ?>"
                  onsubmit="return confirm('Delete this expense permanently?');">
                <?= \App\Core\CSRF::field() ?>
                <button type="submit" class="btn-outline border-error text-error">Delete</button>
            </form>
        <?php endif; ?>
    </div>

    <section class="surface-card space-y-4 p-6">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <p class="label-caps mb-1 text-outline">Amount</p>
                <p class="data-mono text-title-sm font-semibold"><?= e(format_money($expense['amount'])) ?></p>
            </div>
            <div>
                <p class="label-caps mb-1 text-outline">Date</p>
                <p class="data-mono text-body-sm"><?= e(format_date((string) $expense['expense_date'])) ?></p>
            </div>
            <div>
                <p class="label-caps mb-1 text-outline">Category</p>
                <p class="text-body-sm"><?= e((string) $expense['category_name']) ?></p>
            </div>
            <div>
                <p class="label-caps mb-1 text-outline">Recorded by</p>
                <p class="text-body-sm"><?= e((string) ($expense['recorded_by_name'] ?: '—')) ?></p>
                <p class="data-mono text-[11px] text-on-surface-variant">
                    <?= e(format_datetime((string) $expense['created_at'])) ?>
                </p>
            </div>
        </div>

        <?php if (!empty($expense['receipt_path'])): ?>
            <div>
                <p class="label-caps mb-1 text-outline">Receipt</p>
                <a class="btn-outline" href="<?= e(url('/expenses/' . $expense['id'] . '/receipt')) ?>" target="_blank" rel="noopener">
                    <span class="material-symbols-outlined text-[18px]">visibility</span>
                    View receipt
                </a>
            </div>
        <?php endif; ?>
    </section>
</div>
