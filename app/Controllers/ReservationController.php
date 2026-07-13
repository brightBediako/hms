<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Room;
use App\Services\AvailabilityService;
use App\Services\ReservationService;

final class ReservationController
{
    private Reservation $reservations;
    private ReservationService $service;
    private AvailabilityService $availability;
    private Guest $guests;
    private Room $rooms;

    public function __construct(
        ?Reservation $reservations = null,
        ?ReservationService $service = null,
        ?AvailabilityService $availability = null,
        ?Guest $guests = null,
        ?Room $rooms = null,
    ) {
        $this->reservations = $reservations ?? new Reservation();
        $this->service = $service ?? new ReservationService();
        $this->availability = $availability ?? new AvailabilityService();
        $this->guests = $guests ?? new Guest();
        $this->rooms = $rooms ?? new Room();
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

        $payload = $this->validatePayload($request);
        if ($payload === null) {
            redirect('/reservations/create');
        }

        $result = $this->service->create($payload, Auth::id());
        if (!$result['ok']) {
            Session::flash('errors', ['_form' => $result['error']]);
            Session::flash('old', $request->post());
            if (!empty($result['conflicts'])) {
                Session::flash('conflicts', $result['conflicts']);
            }
            redirect('/reservations/create');
        }

        Session::flash('success', 'Reservation created.');
        redirect('/reservations/' . $result['id']);
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
            'canEdit' => Auth::can(\Permission::RESERVATIONS_EDIT) && $reservation['status'] === 'booked',
            'canCancel' => Auth::can(\Permission::RESERVATIONS_CANCEL) && $reservation['status'] === 'booked',
            'reservationService' => $this->service,
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

        $this->renderForm($reservation, []);
    }

    public function update(Request $request, string $id): void
    {
        Auth::requirePermission(\Permission::RESERVATIONS_EDIT);

        $reservationId = (int) $id;
        $payload = $this->validatePayload($request);
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

        $availableRooms = [];
        if ($checkOut > $checkIn) {
            $availableRooms = $this->availability->availableRooms($checkIn, $checkOut, $exceptId);
            // Keep currently selected room in list when editing even if filters change
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
            'guests' => $this->guests->search(null, 500),
            'availableRooms' => $availableRooms,
            'errors' => $errors,
            'old' => $merged,
            'conflicts' => $conflicts,
            'reservationService' => $this->service,
        ], 'app');
    }

    /** @return array<string, mixed>|null */
    private function validatePayload(Request $request): ?array
    {
        $validator = new Validator();
        $data = $validator->validate($request->post(), [
            'guest_id' => 'required|int',
            'room_id' => 'required|int',
            'check_in_date' => 'required|date',
            'check_out_date' => 'required|date',
            'source' => 'required',
            'adults' => 'required|int',
            'children' => 'nullable|int',
            'agreed_rate' => 'required',
            'notes' => 'nullable|max:2000',
        ]);

        if ($data === null) {
            Session::flash('errors', $validator->firstErrors());
            Session::flash('old', $request->post());
            return null;
        }

        $rate = $request->input('agreed_rate');
        if (!is_numeric($rate) || (float) $rate < 0) {
            Session::flash('errors', ['agreed_rate' => 'Enter a valid nightly rate.']);
            Session::flash('old', $request->post());
            return null;
        }

        $children = $data['children'] ?? 0;
        if ($children === null || $children === '') {
            $children = 0;
        }

        return [
            'guest_id' => (int) $data['guest_id'],
            'room_id' => (int) $data['room_id'],
            'check_in_date' => (string) $data['check_in_date'],
            'check_out_date' => (string) $data['check_out_date'],
            'source' => (string) $data['source'],
            'adults' => (int) $data['adults'],
            'children' => (int) $children,
            'agreed_rate' => number_format((float) $rate, 2, '.', ''),
            'notes' => $data['notes'] !== null && $data['notes'] !== '' ? (string) $data['notes'] : null,
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
