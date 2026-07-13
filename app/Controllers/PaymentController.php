<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\BillingService;
use App\Services\PaymentService;

final class PaymentController
{
    private Payment $payments;
    private PaymentService $service;
    private Invoice $invoices;
    private BillingService $billing;

    public function __construct(
        ?Payment $payments = null,
        ?PaymentService $service = null,
        ?Invoice $invoices = null,
        ?BillingService $billing = null,
    ) {
        $this->payments = $payments ?? new Payment();
        $this->service = $service ?? new PaymentService();
        $this->invoices = $invoices ?? new Invoice();
        $this->billing = $billing ?? new BillingService();
    }

    public function index(Request $request): void
    {
        Auth::requirePermission(\Permission::PAYMENTS_RECORD);

        $filters = [
            'method' => $this->stringOrNull($request->input('method')),
            'q' => $this->stringOrNull($request->input('q')),
        ];

        View::render('payments/index', [
            'title' => 'Payments',
            'payments' => $this->payments->filtered($filters),
            'filters' => $filters,
            'paymentService' => $this->service,
            'billingService' => $this->billing,
        ], 'app');
    }

    public function create(Request $request): void
    {
        Auth::requirePermission(\Permission::PAYMENTS_RECORD);

        $invoiceId = (int) ($request->input('invoice_id') ?? 0);
        $invoice = $invoiceId > 0 ? $this->invoices->findById($invoiceId) : null;

        $payable = $this->payableInvoices();

        View::render('payments/form', [
            'title' => 'Record payment',
            'invoice' => $invoice,
            'payableInvoices' => $payable,
            'errors' => Session::pullFlash('errors') ?? [],
            'old' => Session::pullFlash('old') ?? [],
            'paymentService' => $this->service,
        ], 'app');
    }

    public function store(Request $request): void
    {
        Auth::requirePermission(\Permission::PAYMENTS_RECORD);

        $validator = new Validator();
        $data = $validator->validate($request->post(), [
            'invoice_id' => 'required|int',
            'method' => 'required',
            'amount' => 'required',
            'reference_number' => 'nullable|max:100',
            'notes' => 'nullable|max:255',
        ]);

        if ($data === null) {
            Session::flash('errors', $validator->firstErrors());
            Session::flash('old', $request->post());
            Session::flash('payment_errors', $validator->firstErrors());
            Session::flash('payment_old', $request->post());
            $invoiceId = (int) ($request->input('invoice_id') ?? 0);
            if ($request->input('return_to') === 'invoice' && $invoiceId > 0) {
                redirect('/billing/' . $invoiceId);
            }
            redirect('/payments/create' . ($invoiceId > 0 ? '?invoice_id=' . $invoiceId : ''));
        }

        $result = $this->service->record([
            'invoice_id' => (int) $data['invoice_id'],
            'method' => (string) $data['method'],
            'amount' => (string) $data['amount'],
            'reference_number' => $data['reference_number'] ?? null,
            'notes' => $data['notes'] ?? null,
        ], Auth::id());

        if (!$result['ok']) {
            Session::flash('error', $result['error']);
            Session::flash('old', $request->post());
            Session::flash('payment_old', $request->post());
            if ($request->input('return_to') === 'invoice') {
                redirect('/billing/' . (int) $data['invoice_id']);
            }
            redirect('/payments/create?invoice_id=' . (int) $data['invoice_id']);
        }

        Session::flash('success', 'Payment recorded.');
        redirect('/billing/' . (int) $data['invoice_id']);
    }

    /** @return list<array<string, mixed>> */
    private function payableInvoices(): array
    {
        $issued = $this->invoices->filtered(['status' => 'issued'], 100);
        $partial = $this->invoices->filtered(['status' => 'partially_paid'], 100);

        return array_values(array_filter(
            array_merge($issued, $partial),
            static fn (array $row): bool => (float) $row['balance_due'] > 0
        ));
    }

    private function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $str = trim((string) $value);

        return $str === '' ? null : $str;
    }
}
