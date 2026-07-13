<?php
/** @var array<string, mixed> $guest */
/** @var list<array<string, mixed>> $documents */
/** @var list<array<string, mixed>> $stays */
/** @var bool $canManage */
/** @var \App\Services\GuestService $guestService */
/** @var array<string, string> $docErrors */
?>
<div class="space-y-stack-gap">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-body-sm text-on-surface-variant">
                <a href="<?= e(url('/guests')) ?>" class="hover:text-primary">Guests</a> / Profile
            </p>
            <h1 class="text-headline-md text-on-surface"><?= e((string) $guest['full_name']) ?></h1>
            <p class="text-body-sm text-on-surface-variant">
                <?= e((string) ($guest['nationality'] ?: 'Nationality not set')) ?>
                · Added <?= e(format_datetime((string) $guest['created_at'])) ?>
            </p>
        </div>
        <?php if ($canManage): ?>
            <a href="<?= e(url('/guests/' . $guest['id'] . '/edit')) ?>" class="btn-outline">
                <span class="material-symbols-outlined text-[18px]">edit</span>
                Edit
            </a>
        <?php endif; ?>
    </div>

    <div class="grid gap-stack-gap lg:grid-cols-3">
        <section class="surface-card space-y-4 p-6 lg:col-span-2">
            <h2 class="text-title-sm text-on-surface">Contact &amp; ID</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <p class="label-caps mb-1 text-outline">Phone</p>
                    <p class="data-mono text-body-sm"><?= e((string) ($guest['phone'] ?: '—')) ?></p>
                </div>
                <div>
                    <p class="label-caps mb-1 text-outline">Email</p>
                    <p class="text-body-sm"><?= e((string) ($guest['email'] ?: '—')) ?></p>
                </div>
                <div>
                    <p class="label-caps mb-1 text-outline">ID type</p>
                    <p class="text-body-sm"><?= e($guestService->labelForIdType($guest['id_type'] ?? null)) ?></p>
                </div>
                <div>
                    <p class="label-caps mb-1 text-outline">ID number</p>
                    <p class="data-mono text-body-sm"><?= e((string) ($guest['id_number'] ?: '—')) ?></p>
                </div>
                <div class="sm:col-span-2">
                    <p class="label-caps mb-1 text-outline">Address</p>
                    <p class="text-body-sm"><?= e((string) ($guest['address'] ?: '—')) ?></p>
                </div>
                <div class="sm:col-span-2">
                    <p class="label-caps mb-1 text-outline">Notes</p>
                    <p class="text-body-sm whitespace-pre-wrap"><?= e((string) ($guest['notes'] ?: '—')) ?></p>
                </div>
            </div>
        </section>

        <section class="surface-card space-y-4 p-6">
            <h2 class="text-title-sm text-on-surface">Documents</h2>
            <p class="text-body-sm text-on-surface-variant">
                ID scans are stored securely and served only to permitted staff.
            </p>

            <?php if ($canManage): ?>
                <form method="post" action="<?= e(url('/guests/' . $guest['id'] . '/documents')) ?>"
                      enctype="multipart/form-data" class="space-y-3 border-b border-outline-variant pb-4">
                    <?= \App\Core\CSRF::field() ?>
                    <div>
                        <label class="label-caps mb-1 block text-outline" for="document_type">Type</label>
                        <select id="document_type" name="document_type" class="input-field">
                            <option value="id_scan">ID scan</option>
                            <option value="signature">Signature</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="label-caps mb-1 block text-outline" for="document">File</label>
                        <input id="document" name="document" type="file" class="input-field text-body-sm"
                               accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/*" required>
                        <?php if (!empty($docErrors['document'])): ?>
                            <p class="mt-1 text-body-sm text-error"><?= e($docErrors['document']) ?></p>
                        <?php endif; ?>
                        <p class="mt-1 text-[11px] text-on-surface-variant">PDF, JPG, PNG, or WEBP · max 5 MB</p>
                    </div>
                    <button type="submit" class="btn-primary w-full">Upload</button>
                </form>
            <?php endif; ?>

            <?php if ($documents === []): ?>
                <p class="text-body-sm text-on-surface-variant">No documents uploaded.</p>
            <?php else: ?>
                <ul class="space-y-2">
                    <?php foreach ($documents as $doc): ?>
                        <li class="flex items-center justify-between gap-2 rounded border border-outline-variant px-3 py-2">
                            <div class="min-w-0">
                                <p class="truncate text-body-sm text-on-surface">
                                    <?= e((string) ($doc['document_type'] ?: 'document')) ?>
                                </p>
                                <p class="text-[11px] text-on-surface-variant">
                                    <?= e(format_datetime((string) $doc['uploaded_at'])) ?>
                                </p>
                            </div>
                            <div class="flex shrink-0 items-center gap-1">
                                <a class="btn-ghost" href="<?= e(url('/guests/' . $guest['id'] . '/documents/' . $doc['id'] . '/download')) ?>">
                                    View
                                </a>
                                <?php if ($canManage): ?>
                                    <form method="post" action="<?= e(url('/guests/' . $guest['id'] . '/documents/' . $doc['id'] . '/delete')) ?>"
                                          onsubmit="return confirm('Remove this document?');">
                                        <?= \App\Core\CSRF::field() ?>
                                        <button type="submit" class="btn-ghost text-error">Remove</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>

    <section class="surface-card overflow-hidden">
        <div class="border-b border-outline-variant px-6 py-4">
            <h2 class="text-title-sm text-on-surface">Stay history</h2>
            <p class="text-body-sm text-on-surface-variant">
                Linked reservations appear here once bookings are created.
            </p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-surface-container-low">
                    <tr>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Reference</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Room</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Dates</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Status</th>
                        <th class="label-caps px-cell-x py-cell-y text-outline">Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($stays === []): ?>
                        <tr>
                            <td colspan="5" class="px-cell-x py-8 text-center text-body-sm text-on-surface-variant">
                                No stays yet. Stay history will fill in after Reservations (feature 08).
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($stays as $stay): ?>
                        <tr class="border-t border-outline-variant hover:bg-primary/[0.02]">
                            <td class="px-cell-x py-cell-y data-mono text-body-sm">
                                <?= e((string) $stay['booking_reference']) ?>
                            </td>
                            <td class="px-cell-x py-cell-y text-body-sm">
                                <span class="data-mono">#<?= e((string) $stay['room_number']) ?></span>
                                · <?= e((string) $stay['room_type_name']) ?>
                            </td>
                            <td class="px-cell-x py-cell-y data-mono text-body-sm text-on-surface-variant">
                                <?= e(format_date((string) $stay['check_in_date'])) ?>
                                →
                                <?= e(format_date((string) $stay['check_out_date'])) ?>
                            </td>
                            <td class="px-cell-x py-cell-y text-body-sm">
                                <?= e($guestService->labelForReservationStatus((string) $stay['status'])) ?>
                            </td>
                            <td class="px-cell-x py-cell-y data-mono text-body-sm">
                                <?= e(format_money($stay['agreed_rate'])) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
