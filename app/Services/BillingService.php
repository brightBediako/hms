<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Reservation;

final class BillingService
{
    public function __construct(
        private readonly Invoice $invoices = new Invoice(),
        private readonly InvoiceItem $items = new InvoiceItem(),
        private readonly Reservation $reservations = new Reservation(),
    ) {
    }

    public function labelForStatus(string $status): string
    {
        return match ($status) {
            'draft' => 'Draft',
            'issued' => 'Issued',
            'partially_paid' => 'Partially paid',
            'paid' => 'Paid',
            'void' => 'Void',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    public function labelForItemType(string $type): string
    {
        return match ($type) {
            'room_charge' => 'Room charge',
            'service' => 'Service',
            'discount' => 'Discount',
            'tax' => 'Tax',
            'other' => 'Other',
            default => ucfirst($type),
        };
    }

    /** @return array{bg: string, text: string} */
    public function chipClasses(string $status): array
    {
        return match ($status) {
            'draft' => [
                'bg' => 'bg-surface-variant',
                'text' => 'text-on-surface-variant',
            ],
            'issued' => [
                'bg' => 'bg-primary-fixed',
                'text' => 'text-on-primary-fixed-variant',
            ],
            'partially_paid' => [
                'bg' => 'bg-secondary-fixed',
                'text' => 'text-on-secondary-fixed-variant',
            ],
            'paid' => [
                'bg' => 'bg-primary-fixed',
                'text' => 'text-on-primary-fixed-variant',
            ],
            'void' => [
                'bg' => 'bg-error-container',
                'text' => 'text-on-error-container',
            ],
            default => [
                'bg' => 'bg-surface-variant',
                'text' => 'text-on-surface-variant',
            ],
        };
    }

    public function taxRate(): float
    {
        return (new SettingsService())->taxRate();
    }

    /** @return list<array{key: string, label: string, rate: float}> */
    public function taxLines(): array
    {
        return (new SettingsService())->taxLines();
    }

    public function taxLinesLabel(): string
    {
        return (new SettingsService())->taxLinesLabel();
    }

    public function generateInvoiceNumber(): string
    {
        $year = (int) date('Y');
        $seq = $this->invoices->nextNumber($year);

        return sprintf('INV-%d-%06d', $year, $seq);
    }

    public function nightsBetween(string $checkIn, string $checkOut): int
    {
        $start = new \DateTimeImmutable($checkIn);
        $end = new \DateTimeImmutable($checkOut);
        $days = (int) $start->diff($end)->days;

        return max(1, $days);
    }

    /**
     * Create a draft invoice from a reservation (room nights + optional tax).
     * Allowed for booked (advance/deposit), in-house, or checked-out stays.
     *
     * @return array{ok: true, id: int}|array{ok: false, error: string}
     */
    public function generateFromReservation(int $reservationId, ?int $staffId, bool $includeTax = true): array
    {
        $reservation = $this->reservations->findById($reservationId);
        if ($reservation === null) {
            return ['ok' => false, 'error' => 'Reservation not found.'];
        }

        if (!in_array((string) $reservation['status'], ['booked', 'checked_in', 'checked_out'], true)) {
            return ['ok' => false, 'error' => 'Invoices can only be generated for booked, in-house, or checked-out stays.'];
        }

        if ($this->invoices->findActiveForReservation($reservationId) !== null) {
            return ['ok' => false, 'error' => 'An active invoice already exists for this reservation.'];
        }

        $nights = $this->nightsBetween(
            (string) $reservation['check_in_date'],
            (string) $reservation['check_out_date']
        );
        $rate = (float) $reservation['agreed_rate'];
        $roomTotal = round($nights * $rate, 2);
        $isAdvance = (string) $reservation['status'] === 'booked';

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $invoiceId = $this->invoices->create([
                'invoice_number' => $this->generateInvoiceNumber(),
                'reservation_id' => $reservationId,
                'guest_id' => (int) $reservation['guest_id'],
                'subtotal' => 0,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'total_amount' => 0,
                'amount_paid' => 0,
                'balance_due' => 0,
                'status' => 'draft',
            ]);

            $this->items->create([
                'invoice_id' => $invoiceId,
                'item_type' => 'room_charge',
                'description' => sprintf(
                    '%sRoom #%s · %s · %d night(s) @ %s',
                    $isAdvance ? 'Advance · ' : '',
                    (string) $reservation['room_number'],
                    (string) $reservation['room_type_name'],
                    $nights,
                    number_format($rate, 2, '.', '')
                ),
                'quantity' => number_format($nights, 2, '.', ''),
                'unit_price' => number_format($rate, 2, '.', ''),
                'line_total' => number_format($roomTotal, 2, '.', ''),
                'source_module' => 'hms',
            ]);

            if ($includeTax) {
                foreach ($this->taxLines() as $taxLine) {
                    if ($taxLine['rate'] <= 0) {
                        continue;
                    }
                    $tax = round($roomTotal * $taxLine['rate'], 2);
                    if ($tax <= 0) {
                        continue;
                    }
                    $this->items->create([
                        'invoice_id' => $invoiceId,
                        'item_type' => 'tax',
                        'description' => sprintf('%s (%.2f%%)', $taxLine['label'], $taxLine['rate'] * 100),
                        'quantity' => '1.00',
                        'unit_price' => number_format($tax, 2, '.', ''),
                        'line_total' => number_format($tax, 2, '.', ''),
                        'source_module' => 'hms',
                    ]);
                }
            }

            $this->recalculate($invoiceId);
            $pdo->commit();

            $created = $this->invoices->findById($invoiceId);
            $audit = new AuditService();
            $audit->log(
                'invoice.generate',
                'invoices',
                $invoiceId,
                null,
                $audit->snapshot($created, ['invoice_number', 'reservation_id', 'guest_id', 'total_amount', 'status']),
                $staffId,
            );

            return ['ok' => true, 'id' => $invoiceId];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Estimated folio total for stay dates (room nights + optional tax).
     *
     * @return array{nights: int, room_total: float, tax: float, total: float}
     */
    public function estimateStayTotal(string $checkIn, string $checkOut, float $nightlyRate, bool $includeTax = true): array
    {
        $nights = $this->nightsBetween($checkIn, $checkOut);
        $roomTotal = round(max(0, $nights) * max(0, $nightlyRate), 2);
        $tax = ($includeTax && $this->taxRate() > 0) ? round($roomTotal * $this->taxRate(), 2) : 0.0;

        return [
            'nights' => $nights,
            'room_total' => $roomTotal,
            'tax' => $tax,
            'total' => round($roomTotal + $tax, 2),
        ];
    }

    /**
     * @param array{item_type: string, description: string, quantity: float|string, unit_price: float|string} $data
     * @return array{ok: true}|array{ok: false, error: string}
     */
    public function addLineItem(int $invoiceId, array $data): array
    {
        $invoice = $this->invoices->findById($invoiceId);
        if ($invoice === null) {
            return ['ok' => false, 'error' => 'Invoice not found.'];
        }
        if (!in_array((string) $invoice['status'], ['draft', 'issued', 'partially_paid'], true)) {
            return ['ok' => false, 'error' => 'Cannot edit line items on this invoice.'];
        }
        if ((string) $invoice['status'] === 'paid') {
            return ['ok' => false, 'error' => 'Paid invoices cannot be modified.'];
        }

        $type = (string) $data['item_type'];
        if (!in_array($type, InvoiceItem::TYPES, true)) {
            return ['ok' => false, 'error' => 'Invalid line item type.'];
        }

        $qty = (float) $data['quantity'];
        $unit = (float) $data['unit_price'];
        if ($qty <= 0) {
            return ['ok' => false, 'error' => 'Quantity must be greater than zero.'];
        }
        if ($unit < 0) {
            return ['ok' => false, 'error' => 'Unit price cannot be negative.'];
        }

        $lineTotal = round($qty * $unit, 2);
        $this->items->create([
            'invoice_id' => $invoiceId,
            'item_type' => $type,
            'description' => trim((string) $data['description']),
            'quantity' => number_format($qty, 2, '.', ''),
            'unit_price' => number_format($unit, 2, '.', ''),
            'line_total' => number_format($lineTotal, 2, '.', ''),
            'source_module' => 'hms',
        ]);

        $this->recalculate($invoiceId);

        return ['ok' => true];
    }

    /**
     * @return array{ok: true}|array{ok: false, error: string}
     */
    public function removeLineItem(int $invoiceId, int $itemId): array
    {
        $invoice = $this->invoices->findById($invoiceId);
        if ($invoice === null) {
            return ['ok' => false, 'error' => 'Invoice not found.'];
        }
        if ((string) $invoice['status'] !== 'draft') {
            return ['ok' => false, 'error' => 'Line items can only be removed from draft invoices.'];
        }

        $item = $this->items->findById($itemId);
        if ($item === null || (int) $item['invoice_id'] !== $invoiceId) {
            return ['ok' => false, 'error' => 'Line item not found.'];
        }

        $this->items->delete($itemId);
        $this->recalculate($invoiceId);

        return ['ok' => true];
    }

    /**
     * @return array{ok: true}|array{ok: false, error: string}
     */
    public function issue(int $invoiceId, ?int $staffId): array
    {
        $invoice = $this->invoices->findById($invoiceId);
        if ($invoice === null) {
            return ['ok' => false, 'error' => 'Invoice not found.'];
        }
        if ((string) $invoice['status'] !== 'draft') {
            return ['ok' => false, 'error' => 'Only draft invoices can be issued.'];
        }
        if ((float) $invoice['total_amount'] <= 0) {
            return ['ok' => false, 'error' => 'Add line items before issuing.'];
        }

        $this->invoices->issue($invoiceId, $staffId);

        $after = $this->invoices->findById($invoiceId);
        $audit = new AuditService();
        $audit->log(
            'invoice.issue',
            'invoices',
            $invoiceId,
            $audit->snapshot($invoice, ['invoice_number', 'status', 'total_amount']),
            $audit->snapshot($after, ['invoice_number', 'status', 'total_amount', 'issued_at']),
            $staffId,
        );

        return ['ok' => true];
    }

    /**
     * @return array{ok: true}|array{ok: false, error: string}
     */
    public function void(int $invoiceId): array
    {
        $invoice = $this->invoices->findById($invoiceId);
        if ($invoice === null) {
            return ['ok' => false, 'error' => 'Invoice not found.'];
        }
        if ((string) $invoice['status'] === 'void') {
            return ['ok' => false, 'error' => 'Invoice is already void.'];
        }
        if ((float) $invoice['amount_paid'] > 0) {
            return ['ok' => false, 'error' => 'Cannot void an invoice that has payments. Reverse payments first.'];
        }
        if ((string) $invoice['status'] === 'paid') {
            return ['ok' => false, 'error' => 'Cannot void a paid invoice.'];
        }

        $this->invoices->void($invoiceId);

        $audit = new AuditService();
        $audit->log(
            'invoice.void',
            'invoices',
            $invoiceId,
            $audit->snapshot($invoice, ['invoice_number', 'status', 'total_amount', 'balance_due']),
            ['status' => 'void'],
            null,
        );

        return ['ok' => true];
    }

    public function recalculate(int $invoiceId): void
    {
        $invoice = $this->invoices->findById($invoiceId);
        if ($invoice === null || (string) $invoice['status'] === 'void') {
            return;
        }

        $lines = $this->items->forInvoice($invoiceId);
        $subtotal = 0.0;
        $discount = 0.0;
        $tax = 0.0;

        foreach ($lines as $line) {
            $amount = (float) $line['line_total'];
            match ((string) $line['item_type']) {
                'discount' => $discount += $amount,
                'tax' => $tax += $amount,
                default => $subtotal += $amount,
            };
        }

        $subtotal = round($subtotal, 2);
        $discount = round($discount, 2);
        $tax = round($tax, 2);
        $total = round(max(0, $subtotal - $discount + $tax), 2);
        $paid = (float) $invoice['amount_paid'];
        $balance = round(max(0, $total - $paid), 2);

        $status = (string) $invoice['status'];
        if (!in_array($status, ['draft', 'void'], true)) {
            if ($paid <= 0) {
                $status = 'issued';
            } elseif ($balance <= 0.001) {
                $status = 'paid';
            } else {
                $status = 'partially_paid';
            }
        }

        $this->invoices->updateTotals($invoiceId, [
            'subtotal' => number_format($subtotal, 2, '.', ''),
            'discount_amount' => number_format($discount, 2, '.', ''),
            'tax_amount' => number_format($tax, 2, '.', ''),
            'total_amount' => number_format($total, 2, '.', ''),
            'balance_due' => number_format($balance, 2, '.', ''),
            'status' => $status,
        ]);
    }
}
