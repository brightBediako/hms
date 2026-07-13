<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Reservation;
use App\Services\BillingService;
use App\Services\PaymentService;

final class BillingController
{
    private Invoice $invoices;
    private InvoiceItem $items;
    private BillingService $service;
    private Reservation $reservations;
    private Payment $payments;
    private PaymentService $paymentService;

    public function __construct(
        ?Invoice $invoices = null,
        ?InvoiceItem $items = null,
        ?BillingService $service = null,
        ?Reservation $reservations = null,
        ?Payment $payments = null,
        ?PaymentService $paymentService = null,
    ) {
        $this->invoices = $invoices ?? new Invoice();
        $this->items = $items ?? new InvoiceItem();
        $this->service = $service ?? new BillingService();
        $this->reservations = $reservations ?? new Reservation();
        $this->payments = $payments ?? new Payment();
        $this->paymentService = $paymentService ?? new PaymentService();
    }

    public function index(Request $request): void
    {
        Auth::requirePermission(\Permission::BILLING_VIEW);

        $filters = [
            'status' => $this->stringOrNull($request->input('status')),
            'q' => $this->stringOrNull($request->input('q')),
        ];

        View::render('billing/index', [
            'title' => 'Billing & Invoices',
            'invoices' => $this->invoices->filtered($filters),
            'filters' => $filters,
            'canCreate' => Auth::can(\Permission::BILLING_CREATE),
            'billingService' => $this->service,
        ], 'app');
    }

    public function create(Request $request): void
    {
        Auth::requirePermission(\Permission::BILLING_CREATE);

        $eligible = array_merge(
            $this->reservations->filtered(['status' => 'checked_in'], 200),
            $this->reservations->filtered(['status' => 'checked_out'], 200),
        );

        $candidates = [];
        foreach ($eligible as $row) {
            if ($this->invoices->findActiveForReservation((int) $row['id']) !== null) {
                continue;
            }
            $candidates[] = $row;
        }

        View::render('billing/create', [
            'title' => 'Generate invoice',
            'reservations' => $candidates,
            'selectedReservationId' => (int) ($request->input('reservation_id') ?? 0),
            'taxRatePercent' => round($this->service->taxRate() * 100, 2),
            'errors' => Session::pullFlash('errors') ?? [],
        ], 'app');
    }

    public function store(Request $request): void
    {
        Auth::requirePermission(\Permission::BILLING_CREATE);

        $reservationId = (int) ($request->input('reservation_id') ?? 0);
        if ($reservationId < 1) {
            Session::flash('errors', ['reservation_id' => 'Select a reservation.']);
            redirect('/billing/create');
        }

        $includeTax = $request->input('include_tax') === '1' || $request->input('include_tax') === 'on';
        $result = $this->service->generateFromReservation($reservationId, Auth::id(), $includeTax);
        if (!$result['ok']) {
            Session::flash('error', $result['error']);
            redirect('/billing/create?reservation_id=' . $reservationId);
        }

        Session::flash('success', 'Draft invoice created.');
        redirect('/billing/' . $result['id']);
    }

    public function show(Request $request, string $id): void
    {
        Auth::requirePermission(\Permission::BILLING_VIEW);

        $invoice = $this->invoices->findById((int) $id);
        if ($invoice === null) {
            Session::flash('error', 'Invoice not found.');
            redirect('/billing');
        }

        View::render('billing/show', [
            'title' => (string) $invoice['invoice_number'],
            'invoice' => $invoice,
            'items' => $this->items->forInvoice((int) $invoice['id']),
            'payments' => $this->payments->forInvoice((int) $invoice['id']),
            'canCreate' => Auth::can(\Permission::BILLING_CREATE),
            'canVoid' => Auth::can(\Permission::BILLING_VOID),
            'canRecordPayment' => Auth::can(\Permission::PAYMENTS_RECORD),
            'billingService' => $this->service,
            'paymentService' => $this->paymentService,
            'itemErrors' => Session::pullFlash('item_errors') ?? [],
            'itemOld' => Session::pullFlash('item_old') ?? [],
            'paymentErrors' => Session::pullFlash('payment_errors') ?? [],
            'paymentOld' => Session::pullFlash('payment_old') ?? [],
        ], 'app');
    }

    public function itemStore(Request $request, string $id): void
    {
        Auth::requirePermission(\Permission::BILLING_CREATE);

        $invoiceId = (int) $id;
        $validator = new Validator();
        $data = $validator->validate($request->post(), [
            'item_type' => 'required',
            'description' => 'required|max:255',
            'quantity' => 'required',
            'unit_price' => 'required',
        ]);

        if ($data === null) {
            Session::flash('item_errors', $validator->firstErrors());
            Session::flash('item_old', $request->post());
            redirect('/billing/' . $invoiceId);
        }

        $result = $this->service->addLineItem($invoiceId, [
            'item_type' => (string) $data['item_type'],
            'description' => (string) $data['description'],
            'quantity' => (string) $data['quantity'],
            'unit_price' => (string) $data['unit_price'],
        ]);

        if (!$result['ok']) {
            Session::flash('error', $result['error']);
        } else {
            Session::flash('success', 'Line item added.');
        }

        redirect('/billing/' . $invoiceId);
    }

    public function itemDestroy(Request $request, string $id, string $itemId): void
    {
        Auth::requirePermission(\Permission::BILLING_CREATE);

        $result = $this->service->removeLineItem((int) $id, (int) $itemId);
        if (!$result['ok']) {
            Session::flash('error', $result['error']);
        } else {
            Session::flash('success', 'Line item removed.');
        }

        redirect('/billing/' . (int) $id);
    }

    public function issue(Request $request, string $id): void
    {
        Auth::requirePermission(\Permission::BILLING_CREATE);

        $result = $this->service->issue((int) $id, Auth::id());
        if (!$result['ok']) {
            Session::flash('error', $result['error']);
        } else {
            Session::flash('success', 'Invoice issued.');
        }

        redirect('/billing/' . (int) $id);
    }

    public function void(Request $request, string $id): void
    {
        Auth::requirePermission(\Permission::BILLING_VOID);

        $result = $this->service->void((int) $id);
        if (!$result['ok']) {
            Session::flash('error', $result['error']);
        } else {
            Session::flash('success', 'Invoice voided.');
        }

        redirect('/billing/' . (int) $id);
    }

    public function printView(Request $request, string $id): void
    {
        Auth::requirePermission(\Permission::BILLING_VIEW);

        $invoice = $this->invoices->findById((int) $id);
        if ($invoice === null) {
            Session::flash('error', 'Invoice not found.');
            redirect('/billing');
        }

        View::render('billing/print', [
            'title' => (string) $invoice['invoice_number'],
            'invoice' => $invoice,
            'items' => $this->items->forInvoice((int) $invoice['id']),
            'payments' => $this->payments->forInvoice((int) $invoice['id']),
            'billingService' => $this->service,
            'paymentService' => $this->paymentService,
        ], 'print');
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
