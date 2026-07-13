<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Core\View;
use App\Models\Reservation;
use App\Models\ReservationTransfer;
use App\Services\FrontDeskService;
use App\Services\ReservationService;

final class FrontDeskController
{
    private Reservation $reservations;
    private FrontDeskService $service;
    private ReservationService $reservationService;
    private ReservationTransfer $transfers;

    public function __construct(
        ?Reservation $reservations = null,
        ?FrontDeskService $service = null,
        ?ReservationService $reservationService = null,
        ?ReservationTransfer $transfers = null,
    ) {
        $this->reservations = $reservations ?? new Reservation();
        $this->service = $service ?? new FrontDeskService();
        $this->reservationService = $reservationService ?? new ReservationService();
        $this->transfers = $transfers ?? new ReservationTransfer();
    }

    public function index(Request $request): void
    {
        $this->requireAnyFrontDeskPermission();

        $date = $this->dateOrToday($request->input('date'));
        $selectedId = (int) ($request->input('selected') ?? 0);
        $selected = $selectedId > 0 ? $this->reservations->findById($selectedId) : null;

        $arrivals = $this->reservations->arrivalsForDate($date);
        $departures = $this->reservations->departuresForDate($date);
        $inHouse = $this->reservations->inHouse();

        if ($selected === null && $arrivals !== []) {
            $selected = $this->reservations->findById((int) $arrivals[0]['id']);
        }

        $candidateRooms = [];
        if ($selected !== null) {
            $candidateRooms = $this->service->candidateRooms(
                (string) $selected['check_in_date'],
                (string) $selected['check_out_date'],
                (int) $selected['id']
            );
            // Keep current room visible
            $currentId = (int) $selected['room_id'];
            $ids = array_map(static fn (array $r): int => (int) $r['id'], $candidateRooms);
            if (!in_array($currentId, $ids, true)) {
                $current = null;
                foreach (array_merge($arrivals, $departures, $inHouse) as $row) {
                    if ((int) $row['id'] === (int) $selected['id']) {
                        $current = $row;
                        break;
                    }
                }
                if ($current !== null) {
                    array_unshift($candidateRooms, [
                        'id' => $currentId,
                        'room_number' => $current['room_number'],
                        'room_type_name' => $current['room_type_name'],
                        'base_rate' => $selected['agreed_rate'],
                        'status' => $selected['room_status'] ?? 'reserved',
                        'floor' => null,
                    ]);
                }
            }
        }

        View::render('frontdesk/index', [
            'title' => 'Front Desk',
            'date' => $date,
            'arrivals' => $arrivals,
            'departures' => $departures,
            'inHouse' => $inHouse,
            'selected' => $selected,
            'transfers' => $selected ? $this->transfers->forReservation((int) $selected['id']) : [],
            'candidateRooms' => $candidateRooms,
            'canCheckIn' => Auth::can(\Permission::FRONTDESK_CHECKIN),
            'canCheckOut' => Auth::can(\Permission::FRONTDESK_CHECKOUT),
            'canTransfer' => Auth::can(\Permission::FRONTDESK_TRANSFER),
            'reservationService' => $this->reservationService,
        ], 'app');
    }

    public function checkIn(Request $request, string $id): void
    {
        Auth::requirePermission(\Permission::FRONTDESK_CHECKIN);

        $roomId = $request->input('room_id');
        $roomId = $roomId !== null && $roomId !== '' ? (int) $roomId : null;

        $result = $this->service->checkIn((int) $id, $roomId, Auth::id());
        if (!$result['ok']) {
            Session::flash('error', $result['error']);
        } else {
            Session::flash('success', 'Guest checked in.');
        }

        redirect('/frontdesk?selected=' . (int) $id . '&date=' . urlencode($this->dateOrToday($request->input('date'))));
    }

    public function checkOut(Request $request, string $id): void
    {
        Auth::requirePermission(\Permission::FRONTDESK_CHECKOUT);

        $result = $this->service->checkOut((int) $id, Auth::id());
        if (!$result['ok']) {
            Session::flash('error', $result['error']);
        } else {
            Session::flash('success', 'Guest checked out. Housekeeping task created.');
        }

        redirect('/frontdesk?date=' . urlencode($this->dateOrToday($request->input('date'))));
    }

    public function assign(Request $request, string $id): void
    {
        Auth::requirePermission(\Permission::FRONTDESK_CHECKIN);

        $roomId = (int) ($request->input('room_id') ?? 0);
        if ($roomId < 1) {
            Session::flash('error', 'Select a room to assign.');
            redirect('/frontdesk?selected=' . (int) $id);
        }

        $result = $this->service->assignRoom((int) $id, $roomId, Auth::id());
        if (!$result['ok']) {
            Session::flash('error', $result['error']);
        } else {
            Session::flash('success', 'Room assigned.');
        }

        redirect('/frontdesk?selected=' . (int) $id . '&date=' . urlencode($this->dateOrToday($request->input('date'))));
    }

    public function transfer(Request $request, string $id): void
    {
        Auth::requirePermission(\Permission::FRONTDESK_TRANSFER);

        $toRoomId = (int) ($request->input('to_room_id') ?? 0);
        $reason = $request->input('reason');
        $reason = is_string($reason) ? $reason : null;

        if ($toRoomId < 1) {
            Session::flash('error', 'Select a target room.');
            redirect('/frontdesk?selected=' . (int) $id);
        }

        $result = $this->service->transfer((int) $id, $toRoomId, $reason, Auth::id());
        if (!$result['ok']) {
            Session::flash('error', $result['error']);
        } else {
            Session::flash('success', 'Guest transferred. Previous room sent to housekeeping.');
        }

        redirect('/frontdesk?selected=' . (int) $id . '&date=' . urlencode($this->dateOrToday($request->input('date'))));
    }

    public function extend(Request $request, string $id): void
    {
        // Extension is an operational edit of the stay — allow with check-in or transfer permission
        if (!Auth::can(\Permission::FRONTDESK_CHECKIN) && !Auth::can(\Permission::FRONTDESK_TRANSFER)) {
            Auth::requirePermission(\Permission::FRONTDESK_CHECKIN);
        }

        $newCheckOut = $request->input('check_out_date');
        if (!is_string($newCheckOut) || $newCheckOut === '') {
            Session::flash('error', 'Enter a new check-out date.');
            redirect('/frontdesk?selected=' . (int) $id);
        }

        $result = $this->service->extendStay((int) $id, $newCheckOut, Auth::id());
        if (!$result['ok']) {
            Session::flash('error', $result['error']);
        } else {
            Session::flash('success', 'Stay extended.');
        }

        redirect('/frontdesk?selected=' . (int) $id . '&date=' . urlencode($this->dateOrToday($request->input('date'))));
    }

    private function requireAnyFrontDeskPermission(): void
    {
        if (
            Auth::can(\Permission::FRONTDESK_CHECKIN)
            || Auth::can(\Permission::FRONTDESK_CHECKOUT)
            || Auth::can(\Permission::FRONTDESK_TRANSFER)
        ) {
            return;
        }

        Auth::requirePermission(\Permission::FRONTDESK_CHECKIN);
    }

    private function dateOrToday(mixed $value): string
    {
        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            return $value;
        }

        return date('Y-m-d');
    }
}
