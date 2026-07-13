<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Models\Guest;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use App\Services\AvailabilityService;
use App\Services\BillingService;
use App\Services\GuestService;
use App\Services\PaymentService;
use App\Services\ReservationService;

final class ReservationController
{
    private Reservation $reservations;
    private ReservationService $service;
    private AvailabilityService $availability;
    private Guest $guests;
    private Room $rooms;
    private GuestService $guestService;
    private PaymentService $payments;
    private BillingService $billing;
    private Invoice $invoices;

    public function __construct(
        ?Reservation $reservations = null,
        ?ReservationService $service = null,
        ?AvailabilityService $availability = null,
        ?Guest $guests = null,
        ?Room $rooms = null,
        ?GuestService $guestService = null,
        ?PaymentService $payments = null,
        ?BillingService $billing = null,
        ?Invoice $invoices = null,
    ) {
        $this->reservations = $reservations ?? new Reservation();
        $this->service = $service ?? new ReservationService();
        $this->availability = $availability ?? new AvailabilityService();
        $this->guests = $guests ?? new Guest();
        $this->rooms = $rooms ?? new Room();
        $this->guestService = $guestService ?? new GuestService($this->guests);
        $this->payments = $payments ?? new PaymentService();
        $this->billing = $billing ?? new BillingService();
        $this->invoices = $invoices ?? new Invoice();
    }

    public function index(Request $request): void
    {
        Auth::requirePermission(\Permission::RESERVATIONS_VIEW);

        $filters = [
            'status' => $this->stringOrNull($request->input('status')),
            'q' => $this->stringOrNull($request->input('q')),
            'from' => $this->stringOrNull($request->input('from')),
            'to' => $this->stringOrNull($request->input('to')),
        ];

        View::render('reservations/index', [
            'title' => 'Reservations',
            'reservations' => $this->reservations->filtered($filters),
            'filters' => $filters,
            'canCreate' => Auth::can(\Permission::RESERVATIONS_CREATE),
            'reservationService' => $this->service,
        ], 'app');
    }

    public function calendar(Request $request): void
    {
        Auth::requirePermission(\Permission::RESERVATIONS_VIEW);

        $from = $this->stringOrNull($request->input('from')) ?? date('Y-m-d');
        $payload = $this->service->calendarPayload($from, 14);

        View::render('reservations/calendar', [
            'title' => 'Availability calendar',
            'from' => $from,
            'dates' => $payload['dates'],
            'rooms' => $payload['rooms'],
            'canCreate' => Auth::can(\Permission::RESERVATIONS_CREATE),
            'reservationService' => $this->service,
        ], 'app');
    }

    public function create(Request $request): void
    {
        Auth::requirePermission(\Permission::RESERVATIONS_CREATE);

        $checkIn = $this->stringOrNull($request->input('check_in_date')) ?? date('Y-m-d');
        $checkOut = $this->stringOrNull($request->input('check_out_date'))
            ?? (new \DateTimeImmutable($checkIn))->modify('+1 day')->format('Y-m-d');

        $this->renderForm(null, [
            'check_in_date' => $checkIn,
            'check_out_date' => $checkOut,
            'check_out_time' => ReservationService::STANDARD_CHECK_OUT_TIME,
            'guest_mode' => 'new',
            'guest_id' => $request->input('guest_id'),
            'room_id' => $request->input('room_id'),
            'source' => 'advance',
            'adults' => 1,
            'children' => 0,
        ]);
    }

    public function store(Request $request): void
    {
        Auth::requirePermission(\Permission::RESERVATIONS_CREATE);

        $payload = $this->validatePayload($request, true);
        if ($payload === null) {
            redirect('/reservations/create');
        }

        $payment = $payload['payment'] ?? null;
        unset($payload['payment']);

        $result = $this->service->create($payload, Auth::id());
        if (!$result['ok']) {
            Session::flash('errors', ['_form' => $result['error']]);
            Session::flash('old', $request->post());
            if (!empty($result['conflicts'])) {
                Session::flash('conflicts', $result['conflicts']);
            }
            redirect('/reservations/create');
        }

        $reservationId = $result['id'];
        $message = 'Reservation created.';

        if (is_array($payment) && (float) $payment['amount'] > 0) {
            $collected = $this->payments->collectForReservation($reservationId, $payment, Auth::id());
            if (!$collected['ok']) {
                Session::flash('success', $message);
                Session::flash('error', 'Payment was not recorded: ' . $collected['error']);
                redirect('/reservations/' . $reservationId);
            }
            $invoice = $this->invoices->findById($collected['invoice_id']);
            $paid = format_money($payment['amount']);
            $balance = $invoice !== null ? format_money($invoice['balance_due']) : '';
            $message = 'Reservation created. Payment of ' . $paid . ' recorded'
                . ($balance !== '' ? ' (balance due ' . $balance . ').' : '.');
        }

        Session::flash('success', $message);
        redirect('/reservations/' . $reservationId);
    }

    public function show(Request $request, string $id): void
    {
        Auth::requirePermission(\Permission::RESERVATIONS_VIEW);

        $reservation = $this->reservations->findById((int) $id);
        if ($reservation === null) {
            Session::flash('error', 'Reservation not found.');
            redirect('/reservations');
        }

        View::render('reservations/show', [
            'title' => (string) $reservation['booking_reference'],
            'reservation' => $reservation,
            'invoice' => $this->invoices->findActiveForReservation((int) $id),
            'canEdit' => Auth::can(\Permission::RESERVATIONS_EDIT) && $reservation['status'] === 'booked',
            'canCancel' => Auth::can(\Permission::RESERVATIONS_CANCEL) && $reservation['status'] === 'booked',
            'canViewBilling' => Auth::can(\Permission::BILLING_VIEW),
            'reservationService' => $this->service,
            'billingService' => $this->billing,
        ], 'app');
    }

    public function edit(Request $request, string $id): void
    {
        Auth::requirePermission(\Permission::RESERVATIONS_EDIT);

        $reservation = $this->reservations->findById((int) $id);
        if ($reservation === null) {
            Session::flash('error', 'Reservation not found.');
            redirect('/reservations');
        }

        if ($reservation['status'] !== 'booked') {
            Session::flash('error', 'Only booked reservations can be edited.');
            redirect('/reservations/' . $id);
        }

        $this->renderForm($reservation, [
            'guest_mode' => 'returning',
        ]);
    }

    public function update(Request $request, string $id): void
    {
        Auth::requirePermission(\Permission::RESERVATIONS_EDIT);

        $reservationId = (int) $id;
        $payload = $this->validatePayload($request, false);
        if ($payload === null) {
            redirect('/reservations/' . $reservationId . '/edit');
        }

        $result = $this->service->update($reservationId, $payload, Auth::id());
        if (!$result['ok']) {
            Session::flash('errors', ['_form' => $result['error']]);
            Session::flash('old', $request->post());
            if (!empty($result['conflicts'])) {
                Session::flash('conflicts', $result['conflicts']);
            }
            redirect('/reservations/' . $reservationId . '/edit');
        }

        Session::flash('success', 'Reservation updated.');
        redirect('/reservations/' . $reservationId);
    }

    public function cancel(Request $request, string $id): void
    {
        Auth::requirePermission(\Permission::RESERVATIONS_CANCEL);

        $reason = $this->stringOrNull($request->input('cancellation_reason'));
        $result = $this->service->cancel((int) $id, $reason, Auth::id());
        if (!$result['ok']) {
            Session::flash('error', $result['error']);
        } else {
            Session::flash('success', 'Reservation cancelled.');
        }

        redirect('/reservations/' . $id);
    }

    public function availability(Request $request): void
    {
        Auth::requirePermission(\Permission::RESERVATIONS_VIEW);

        $checkIn = $this->stringOrNull($request->input('check_in_date'));
        $checkOut = $this->stringOrNull($request->input('check_out_date'));
        $exceptId = (int) ($request->input('except_id') ?? 0);

        if ($checkIn === null || $checkOut === null || $checkOut <= $checkIn) {
            \App\Core\Response::json(['rooms' => [], 'error' => 'Invalid dates'], 422);
            return;
        }

        $rooms = $this->availability->availableRooms(
            $checkIn,
            $checkOut,
            $exceptId > 0 ? $exceptId : null
        );

        $payload = array_map(static function (array $room): array {
            return [
                'id' => (int) $room['id'],
                'room_number' => (string) $room['room_number'],
                'room_type_name' => (string) $room['room_type_name'],
                'base_rate' => (string) $room['base_rate'],
                'status' => (string) $room['status'],
                'floor' => $room['floor'],
            ];
        }, $rooms);

        \App\Core\Response::json(['rooms' => $payload]);
    }

    /** @param array<string, mixed> $defaults */
    private function renderForm(?array $reservation, array $defaults): void
    {
        $old = Session::pullFlash('old') ?? [];
        $errors = Session::pullFlash('errors') ?? [];
        $conflicts = Session::pullFlash('conflicts') ?? [];

        $merged = array_merge($defaults, $reservation ?? [], is_array($old) ? $old : []);

        $checkIn = (string) ($merged['check_in_date'] ?? date('Y-m-d'));
        $checkOut = (string) ($merged['check_out_date'] ?? (new \DateTimeImmutable($checkIn))->modify('+1 day')->format('Y-m-d'));
        $exceptId = $reservation !== null ? (int) $reservation['id'] : null;

        if (!isset($merged['check_in_time']) || $merged['check_in_time'] === '') {
            $merged['check_in_time'] = $reservation !== null
                ? $this->service->timeForInput((string) ($reservation['check_in_time'] ?? ''), date('H:i'))
                : date('H:i');
        } else {
            $merged['check_in_time'] = $this->service->timeForInput((string) $merged['check_in_time'], date('H:i'));
        }
        $merged['check_out_time'] = ReservationService::STANDARD_CHECK_OUT_TIME;

        if (!isset($merged['guest_mode']) || !in_array((string) $merged['guest_mode'], ['new', 'returning'], true)) {
            $merged['guest_mode'] = $reservation !== null || !empty($merged['guest_id']) ? 'returning' : 'new';
        }

        $selectedGuest = null;
        $guestId = (int) ($merged['guest_id'] ?? 0);
        if ($guestId > 0) {
            $selectedGuest = $this->guests->findById($guestId);
        }

        $availableRooms = [];
        if ($checkOut > $checkIn) {
            $availableRooms = $this->availability->availableRooms($checkIn, $checkOut, $exceptId);
            $selectedRoomId = (int) ($merged['room_id'] ?? 0);
            if ($selectedRoomId > 0) {
                $ids = array_map(static fn (array $r): int => (int) $r['id'], $availableRooms);
                if (!in_array($selectedRoomId, $ids, true)) {
                    $selected = $this->rooms->findById($selectedRoomId);
                    if ($selected !== null) {
                        $availableRooms[] = $selected;
                    }
                }
            }
        }

        View::render('reservations/form', [
            'title' => $reservation ? 'Edit reservation' : 'New reservation',
            'reservation' => $reservation,
            'selectedGuest' => $selectedGuest,
            'availableRooms' => $availableRooms,
            'errors' => $errors,
            'old' => $merged,
            'conflicts' => $conflicts,
            'reservationService' => $this->service,
            'guestService' => $this->guestService,
            'guestSearchUrl' => url('/guests/search'),
            'canCollectPayment' => !$reservation && Auth::can(\Permission::PAYMENTS_RECORD) && Auth::can(\Permission::BILLING_CREATE),
            'paymentService' => $this->payments,
            'taxRate' => $this->billing->taxRate(),
            'taxLinesLabel' => $this->billing->taxLinesLabel(),
            'currency' => (new \App\Services\SettingsService())->currency(),
        ], 'app');
    }

    /** @return array<string, mixed>|null */
    private function validatePayload(Request $request, bool $allowPayment): ?array
    {
        $post = $request->post();
        $guestMode = (string) ($post['guest_mode'] ?? 'new');
        if (!in_array($guestMode, ['new', 'returning'], true)) {
            $guestMode = 'new';
        }

        $validator = new Validator();
        $rules = [
            'room_id' => 'required|int',
            'check_in_date' => 'required|date',
            'check_out_date' => 'required|date',
            'source' => 'required',
            'adults' => 'required|int',
            'children' => 'nullable|int',
            'agreed_rate' => 'required',
            'notes' => 'nullable|max:2000',
        ];

        if ($guestMode === 'returning') {
            $rules['guest_id'] = 'required|int';
        }

        $data = $validator->validate($post, $rules);
        if ($data === null) {
            Session::flash('errors', $validator->firstErrors());
            Session::flash('old', $post);
            return null;
        }

        $rate = $request->input('agreed_rate');
        if (!is_numeric($rate) || (float) $rate < 0) {
            Session::flash('errors', ['agreed_rate' => 'Enter a valid nightly rate.']);
            Session::flash('old', $post);
            return null;
        }

        $children = $data['children'] ?? 0;
        if ($children === null || $children === '') {
            $children = 0;
        }

        $payload = [
            'room_id' => (int) $data['room_id'],
            'check_in_date' => (string) $data['check_in_date'],
            'check_out_date' => (string) $data['check_out_date'],
            'check_out_time' => ReservationService::STANDARD_CHECK_OUT_TIME,
            'source' => (string) $data['source'],
            'adults' => (int) $data['adults'],
            'children' => (int) $children,
            'agreed_rate' => number_format((float) $rate, 2, '.', ''),
            'notes' => $data['notes'] !== null && $data['notes'] !== '' ? (string) $data['notes'] : null,
        ];

        if ($guestMode === 'returning') {
            $payload['guest_id'] = (int) $data['guest_id'];
        } else {
            $guestInput = [
                'full_name' => $post['guest_full_name'] ?? '',
                'phone' => $post['guest_phone'] ?? '',
                'email' => $post['guest_email'] ?? '',
                'id_type' => $post['guest_id_type'] ?? '',
                'id_number' => $post['guest_id_number'] ?? '',
                'nationality' => $post['guest_nationality'] ?? '',
                'address' => $post['guest_address'] ?? '',
                'notes' => $post['guest_notes'] ?? '',
            ];
            $guestResult = $this->guestService->normalizeFromInput($guestInput);
            if (!$guestResult['ok']) {
                $mapped = [];
                foreach ($guestResult['errors'] as $field => $message) {
                    $mapped['guest_' . $field] = $message;
                }
                Session::flash('errors', $mapped);
                Session::flash('old', $post);
                return null;
            }
            $payload['new_guest'] = $guestResult['data'];
        }

        if ($allowPayment) {
            $paymentResult = $this->validatePaymentInput($post, (string) $data['check_in_date'], (string) $data['check_out_date'], (float) $rate);
            if ($paymentResult === false) {
                return null;
            }
            if ($paymentResult !== null) {
                $payload['payment'] = $paymentResult;
            }
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $post
     * @return array<string, mixed>|null|false null = no payment; false = validation failed; array = payment payload
     */
    private function validatePaymentInput(array $post, string $checkIn, string $checkOut, float $rate): array|false|null
    {
        $rawAmount = trim((string) ($post['payment_amount'] ?? ''));
        if ($rawAmount === '' || (float) $rawAmount <= 0) {
            return null;
        }

        if (!Auth::can(\Permission::PAYMENTS_RECORD) || !Auth::can(\Permission::BILLING_CREATE)) {
            Session::flash('errors', ['payment_amount' => 'You do not have permission to record payments.']);
            Session::flash('old', $post);
            return false;
        }

        if (!is_numeric($rawAmount)) {
            Session::flash('errors', ['payment_amount' => 'Enter a valid payment amount.']);
            Session::flash('old', $post);
            return false;
        }

        $amount = round((float) $rawAmount, 2);
        $includeTax = (string) ($post['payment_include_tax'] ?? '0') === '1';
        $estimate = $this->billing->estimateStayTotal($checkIn, $checkOut, $rate, $includeTax);
        if ($amount > $estimate['total'] + 0.001) {
            Session::flash('errors', [
                'payment_amount' => 'Amount cannot exceed estimated stay total (' . number_format($estimate['total'], 2) . ').',
            ]);
            Session::flash('old', $post);
            return false;
        }

        $method = (string) ($post['payment_method'] ?? 'cash');
        if (!in_array($method, Payment::METHODS, true)) {
            Session::flash('errors', ['payment_method' => 'Select a valid payment method.']);
            Session::flash('old', $post);
            return false;
        }

        $ref = trim((string) ($post['payment_reference'] ?? ''));
        $notes = trim((string) ($post['payment_notes'] ?? ''));

        return [
            'amount' => $amount,
            'method' => $method,
            'reference_number' => $ref !== '' ? $ref : null,
            'notes' => $notes !== '' ? $notes : 'Payment at booking',
            'include_tax' => $includeTax,
        ];
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
