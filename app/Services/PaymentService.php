<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Invoice;
use App\Models\Payment;

final class PaymentService
{
    public function __construct(
        private readonly Payment $payments = new Payment(),
        private readonly Invoice $invoices = new Invoice(),
        private readonly BillingService $billing = new BillingService(),
    ) {
    }

    public function labelForMethod(string $method): string
    {
        return match ($method) {
            'cash' => 'Cash',
            'mobile_money' => 'Mobile money',
            'card' => 'Card',
            'bank_transfer' => 'Bank transfer',
            'other' => 'Other',
            default => ucfirst(str_replace('_', ' ', $method)),
        };
    }

    /**
     * @param array{invoice_id: int, method: string, amount: float|string, reference_number?: ?string, notes?: ?string} $data
     * @return array{ok: true, id: int}|array{ok: false, error: string}
     */
    public function record(array $data, ?int $staffId): array
    {
        $invoiceId = (int) $data['invoice_id'];
        $invoice = $this->invoices->findById($invoiceId);
        if ($invoice === null) {
            return ['ok' => false, 'error' => 'Invoice not found.'];
        }

        if (!in_array((string) $invoice['status'], ['issued', 'partially_paid'], true)) {
            return ['ok' => false, 'error' => 'Payments can only be recorded against issued or partially paid invoices.'];
        }

        $method = (string) $data['method'];
        if (!in_array($method, Payment::METHODS, true)) {
            return ['ok' => false, 'error' => 'Select a valid payment method.'];
        }

        $amount = round((float) $data['amount'], 2);
        if ($amount <= 0) {
            return ['ok' => false, 'error' => 'Amount must be greater than zero.'];
        }

        $balance = round((float) $invoice['balance_due'], 2);
        if ($amount > $balance + 0.001) {
            return ['ok' => false, 'error' => 'Amount exceeds balance due (' . number_format($balance, 2) . ').'];
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $id = $this->payments->create([
                'invoice_id' => $invoiceId,
                'method' => $method,
                'amount' => number_format($amount, 2, '.', ''),
                'reference_number' => $this->nullableString($data['reference_number'] ?? null),
                'received_by' => $staffId,
                'notes' => $this->nullableString($data['notes'] ?? null),
            ]);

            $paidTotal = $this->payments->sumForInvoice($invoiceId);
            $this->invoices->setAmountPaid($invoiceId, number_format($paidTotal, 2, '.', ''));
            $this->billing->recalculate($invoiceId);

            $pdo->commit();

            $invoice = $this->invoices->findById($invoiceId);
            if ($invoice !== null) {
                (new NotificationService())->paymentRecorded(
                    $id,
                    $invoiceId,
                    (string) $invoice['invoice_number'],
                    format_money($amount),
                    $staffId,
                );
            }

            $audit = new AuditService();
            $audit->log(
                'payment.record',
                'payments',
                $id,
                null,
                [
                    'invoice_id' => $invoiceId,
                    'method' => $method,
                    'amount' => number_format($amount, 2, '.', ''),
                ],
                $staffId,
            );

            return ['ok' => true, 'id' => $id];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Create (or reuse) an advance invoice for a reservation, issue it, and record payment.
     *
     * @param array{amount: float|string, method: string, reference_number?: ?string, notes?: ?string, include_tax?: bool} $data
     * @return array{ok: true, invoice_id: int, payment_id: int}|array{ok: false, error: string}
     */
    public function collectForReservation(int $reservationId, array $data, ?int $staffId): array
    {
        $amount = round((float) ($data['amount'] ?? 0), 2);
        if ($amount <= 0) {
            return ['ok' => false, 'error' => 'Enter a payment amount greater than zero.'];
        }

        $includeTax = ($data['include_tax'] ?? true) !== false;
        $invoice = $this->invoices->findActiveForReservation($reservationId);

        if ($invoice === null) {
            $generated = $this->billing->generateFromReservation($reservationId, $staffId, $includeTax);
            if (!$generated['ok']) {
                return ['ok' => false, 'error' => $generated['error']];
            }
            $invoice = $this->invoices->findById($generated['id']);
        }

        if ($invoice === null) {
            return ['ok' => false, 'error' => 'Could not create invoice for payment.'];
        }

        $invoiceId = (int) $invoice['id'];
        if ((string) $invoice['status'] === 'draft') {
            $issued = $this->billing->issue($invoiceId, $staffId);
            if (!$issued['ok']) {
                return ['ok' => false, 'error' => $issued['error']];
            }
            $invoice = $this->invoices->findById($invoiceId);
            if ($invoice === null) {
                return ['ok' => false, 'error' => 'Invoice missing after issue.'];
            }
        }

        if (!in_array((string) $invoice['status'], ['issued', 'partially_paid'], true)) {
            return ['ok' => false, 'error' => 'This reservation invoice cannot accept payments (status: ' . $invoice['status'] . ').'];
        }

        $recorded = $this->record([
            'invoice_id' => $invoiceId,
            'method' => (string) ($data['method'] ?? 'cash'),
            'amount' => $amount,
            'reference_number' => $data['reference_number'] ?? null,
            'notes' => $data['notes'] ?? 'Payment at booking',
        ], $staffId);

        if (!$recorded['ok']) {
            return ['ok' => false, 'error' => $recorded['error']];
        }

        return [
            'ok' => true,
            'invoice_id' => $invoiceId,
            'payment_id' => $recorded['id'],
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $str = trim((string) $value);

        return $str === '' ? null : $str;
    }
}
