<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Models\RatePlan;
use App\Models\Room;
use App\Models\RoomType;
use App\Services\RoomService;
use App\Services\RoomTypeService;

final class RoomController
{
    private RoomType $roomTypes;
    private RatePlan $ratePlans;
    private RoomTypeService $service;
    private Room $rooms;
    private RoomService $roomService;

    public function __construct(
        ?RoomType $roomTypes = null,
        ?RatePlan $ratePlans = null,
        ?RoomTypeService $service = null,
        ?Room $rooms = null,
        ?RoomService $roomService = null,
    ) {
        $this->roomTypes = $roomTypes ?? new RoomType();
        $this->ratePlans = $ratePlans ?? new RatePlan();
        $this->service = $service ?? new RoomTypeService();
        $this->rooms = $rooms ?? new Room();
        $this->roomService = $roomService ?? new RoomService($this->rooms);
    }

    public function index(Request $request): void
    {
        Auth::requirePermission(\Permission::ROOMS_VIEW);

        $typeIds = $request->query()['type_ids'] ?? [];
        if (!is_array($typeIds)) {
            $typeIds = $typeIds !== '' && $typeIds !== null ? [(int) $typeIds] : [];
        } else {
            $typeIds = array_map('intval', $typeIds);
        }

        $statuses = $request->query()['statuses'] ?? [];
        if (!is_array($statuses)) {
            $statuses = $statuses !== '' && $statuses !== null ? [(string) $statuses] : [];
        } else {
            $statuses = array_map('strval', $statuses);
        }

        // Default: show all statuses when none selected
        if ($statuses === []) {
            $statuses = Room::STATUSES;
        }

        $filters = [
            'floor' => $request->input('floor') !== null && $request->input('floor') !== ''
                ? (string) $request->input('floor')
                : null,
            'type_ids' => $typeIds,
            'statuses' => $statuses,
            'q' => $request->input('q') !== null && $request->input('q') !== ''
                ? (string) $request->input('q')
                : null,
        ];

        $rooms = $this->rooms->filtered($filters);
        $selectedId = (int) ($request->input('selected') ?? 0);
        $selected = $selectedId > 0 ? $this->rooms->findById($selectedId) : null;
        if ($selected === null && $rooms !== []) {
            $selected = $this->rooms->findById((int) $rooms[0]['id']);
        }

        $statusCounts = $this->rooms->statusCounts();

        View::render('rooms/index', [
            'title' => 'Room Inventory',
            'rooms' => $rooms,
            'selected' => $selected,
            'selectedHistory' => $selected ? $this->rooms->statusHistory((int) $selected['id']) : [],
            'selectedAmenities' => $selected
                ? $this->service->decodeAmenities($selected['room_type_amenities'] ?? null)
                : [],
            'types' => $this->roomTypes->all(),
            'floors' => $this->rooms->distinctFloors(),
            'filters' => $filters,
            'statusCounts' => $statusCounts,
            'occupancyPercent' => $this->roomService->occupancyPercent($statusCounts),
            'canManage' => Auth::can(\Permission::ROOMS_MANAGE),
            'roomService' => $this->roomService,
            'typeService' => $this->service,
        ], 'app');
    }

    public function create(Request $request): void
    {
        Auth::requirePermission(\Permission::ROOMS_MANAGE);

        View::render('rooms/form', [
            'title' => 'Add Room',
            'room' => null,
            'types' => $this->roomTypes->all(),
            'statuses' => Room::STATUSES,
            'errors' => Session::pullFlash('errors') ?? [],
            'old' => Session::pullFlash('old') ?? [],
            'roomService' => $this->roomService,
        ], 'app');
    }

    public function store(Request $request): void
    {
        Auth::requirePermission(\Permission::ROOMS_MANAGE);

        $payload = $this->validateRoomPayload($request);
        if ($payload === null) {
            redirect('/rooms/create');
        }

        $payload['changed_by'] = Auth::id();
        $id = $this->rooms->create($payload);
        Session::flash('success', 'Room added to inventory.');
        redirect('/rooms?selected=' . $id);
    }

    public function update(Request $request, string $id): void
    {
        Auth::requirePermission(\Permission::ROOMS_MANAGE);

        $roomId = (int) $id;
        $existing = $this->rooms->findById($roomId);
        if ($existing === null) {
            Session::flash('error', 'Room not found.');
            redirect('/rooms');
        }

        $payload = $this->validateRoomPayload($request, $roomId);
        if ($payload === null) {
            redirect('/rooms?selected=' . $roomId);
        }

        $payload['changed_by'] = Auth::id();
        $payload['status_reason'] = (string) ($request->input('status_reason') ?: 'Manual status update');
        $this->rooms->update($roomId, $payload, (string) $existing['status']);
        Session::flash('success', 'Room updated.');
        redirect('/rooms?selected=' . $roomId);
    }

    /** @return array<string, mixed>|null */
    private function validateRoomPayload(Request $request, ?int $exceptId = null): ?array
    {
        $validator = new Validator();
        $data = $validator->validate($request->post(), [
            'room_type_id' => 'required|int',
            'room_number' => 'required|max:20',
            'floor' => 'nullable|max:20',
            'status' => 'required',
            'notes' => 'nullable|max:255',
        ]);

        $status = (string) $request->input('status', '');
        if ($data === null || !in_array($status, Room::STATUSES, true)) {
            Session::flash('errors', $validator->firstErrors() ?: ['status' => 'Select a valid status.']);
            Session::flash('old', $request->post());
            return null;
        }

        $roomNumber = trim((string) $data['room_number']);
        if ($this->rooms->roomNumberExists($roomNumber, $exceptId)) {
            Session::flash('errors', ['room_number' => 'That room number is already in use.']);
            Session::flash('old', $request->post());
            return null;
        }

        if ($this->roomTypes->findById((int) $data['room_type_id']) === null) {
            Session::flash('errors', ['room_type_id' => 'Select a valid room type.']);
            Session::flash('old', $request->post());
            return null;
        }

        return [
            'room_type_id' => (int) $data['room_type_id'],
            'room_number' => $roomNumber,
            'floor' => $data['floor'] !== null && $data['floor'] !== '' ? (string) $data['floor'] : null,
            'status' => $status,
            'notes' => $data['notes'] !== null && $data['notes'] !== '' ? (string) $data['notes'] : null,
        ];
    }

    public function typesIndex(Request $request): void
    {
        Auth::requirePermission(\Permission::ROOMS_VIEW);

        View::render('rooms/types', [
            'title' => 'Room Types',
            'types' => $this->roomTypes->all(),
            'canManage' => Auth::can(\Permission::ROOMS_MANAGE),
        ], 'app');
    }

    public function typesCreate(Request $request): void
    {
        Auth::requirePermission(\Permission::ROOMS_MANAGE);

        View::render('rooms/type_form', [
            'title' => 'New Room Type',
            'type' => null,
            'amenitiesSelected' => [],
            'amenityOptions' => RoomTypeService::AMENITY_OPTIONS,
            'errors' => Session::pullFlash('errors') ?? [],
            'old' => Session::pullFlash('old') ?? [],
        ], 'app');
    }

    public function typesStore(Request $request): void
    {
        Auth::requirePermission(\Permission::ROOMS_MANAGE);

        $payload = $this->validateTypePayload($request);
        if ($payload === null) {
            redirect('/rooms/types/create');
        }

        $id = $this->roomTypes->create($payload);
        Session::flash('success', 'Room type created.');
        redirect('/rooms/types/' . $id);
    }

    public function typesShow(Request $request, string $id): void
    {
        Auth::requirePermission(\Permission::ROOMS_VIEW);

        $typeId = (int) $id;
        $type = $this->roomTypes->findById($typeId);
        if ($type === null) {
            Session::flash('error', 'Room type not found.');
            redirect('/rooms/types');
        }

        View::render('rooms/type_show', [
            'title' => (string) $type['name'],
            'type' => $type,
            'amenities' => $this->service->decodeAmenities($type['amenities'] ?? null),
            'ratePlans' => $this->ratePlans->forRoomType($typeId),
            'canManage' => Auth::can(\Permission::ROOMS_MANAGE),
            'amenityOptions' => RoomTypeService::AMENITY_OPTIONS,
            'rateErrors' => Session::pullFlash('rate_errors') ?? [],
            'rateOld' => Session::pullFlash('rate_old') ?? [],
        ], 'app');
    }

    public function typesEdit(Request $request, string $id): void
    {
        Auth::requirePermission(\Permission::ROOMS_MANAGE);

        $type = $this->roomTypes->findById((int) $id);
        if ($type === null) {
            Session::flash('error', 'Room type not found.');
            redirect('/rooms/types');
        }

        View::render('rooms/type_form', [
            'title' => 'Edit Room Type',
            'type' => $type,
            'amenitiesSelected' => $this->service->decodeAmenities($type['amenities'] ?? null),
            'amenityOptions' => RoomTypeService::AMENITY_OPTIONS,
            'errors' => Session::pullFlash('errors') ?? [],
            'old' => Session::pullFlash('old') ?? [],
        ], 'app');
    }

    public function typesUpdate(Request $request, string $id): void
    {
        Auth::requirePermission(\Permission::ROOMS_MANAGE);

        $typeId = (int) $id;
        if ($this->roomTypes->findById($typeId) === null) {
            Session::flash('error', 'Room type not found.');
            redirect('/rooms/types');
        }

        $payload = $this->validateTypePayload($request);
        if ($payload === null) {
            redirect('/rooms/types/' . $typeId . '/edit');
        }

        $this->roomTypes->update($typeId, $payload);
        Session::flash('success', 'Room type updated.');
        redirect('/rooms/types/' . $typeId);
    }

    public function typesDestroy(Request $request, string $id): void
    {
        Auth::requirePermission(\Permission::ROOMS_MANAGE);

        $typeId = (int) $id;
        if (!$this->roomTypes->delete($typeId)) {
            Session::flash('error', 'Cannot delete a room type that still has rooms assigned.');
            redirect('/rooms/types/' . $typeId);
        }

        Session::flash('success', 'Room type deleted.');
        redirect('/rooms/types');
    }

    public function rateStore(Request $request, string $id): void
    {
        Auth::requirePermission(\Permission::ROOMS_MANAGE);

        $typeId = (int) $id;
        if ($this->roomTypes->findById($typeId) === null) {
            Session::flash('error', 'Room type not found.');
            redirect('/rooms/types');
        }

        $validator = new Validator();
        $data = $validator->validate($request->post(), [
            'name' => 'required|max:80',
            'rate' => 'required',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        if ($data === null || !is_numeric($request->input('rate'))) {
            Session::flash('rate_errors', $validator->firstErrors() ?: ['rate' => 'Enter a valid rate.']);
            Session::flash('rate_old', $request->post());
            redirect('/rooms/types/' . $typeId);
        }

        $this->ratePlans->create([
            'room_type_id' => $typeId,
            'name' => (string) $data['name'],
            'rate' => number_format((float) $request->input('rate'), 2, '.', ''),
            'start_date' => $data['start_date'] !== null && $data['start_date'] !== '' ? $data['start_date'] : null,
            'end_date' => $data['end_date'] !== null && $data['end_date'] !== '' ? $data['end_date'] : null,
            'is_active' => $request->input('is_active') ? 1 : 0,
        ]);

        Session::flash('success', 'Rate plan added.');
        redirect('/rooms/types/' . $typeId);
    }

    public function rateDestroy(Request $request, string $id, string $rateId): void
    {
        Auth::requirePermission(\Permission::ROOMS_MANAGE);

        $typeId = (int) $id;
        $plan = $this->ratePlans->findById((int) $rateId);
        if ($plan === null || (int) $plan['room_type_id'] !== $typeId) {
            Session::flash('error', 'Rate plan not found.');
            redirect('/rooms/types/' . $typeId);
        }

        $this->ratePlans->delete((int) $rateId);
        Session::flash('success', 'Rate plan removed.');
        redirect('/rooms/types/' . $typeId);
    }

    /** @return array<string, mixed>|null */
    private function validateTypePayload(Request $request): ?array
    {
        $validator = new Validator();
        $data = $validator->validate($request->post(), [
            'name' => 'required|max:80',
            'description' => 'nullable|max:2000',
            'base_capacity_adults' => 'required|int',
            'base_capacity_children' => 'required|int',
            'base_rate' => 'required',
            'extra_bed_rate' => 'nullable',
        ]);

        $baseRate = $request->input('base_rate');
        $extraBed = $request->input('extra_bed_rate');

        if ($data === null || !is_numeric($baseRate)) {
            Session::flash('errors', $validator->firstErrors() ?: ['base_rate' => 'Enter a valid base rate.']);
            Session::flash('old', array_merge($request->post(), [
                'amenities' => $request->input('amenities', []),
            ]));
            return null;
        }

        if ($extraBed !== null && $extraBed !== '' && !is_numeric($extraBed)) {
            Session::flash('errors', ['extra_bed_rate' => 'Enter a valid extra bed rate.']);
            Session::flash('old', array_merge($request->post(), [
                'amenities' => $request->input('amenities', []),
            ]));
            return null;
        }

        $amenities = $request->input('amenities', []);
        if (!is_array($amenities)) {
            $amenities = [];
        }

        /** @var list<string> $amenityList */
        $amenityList = array_values(array_filter($amenities, 'is_string'));

        return [
            'name' => (string) $data['name'],
            'description' => $data['description'] !== null && $data['description'] !== ''
                ? (string) $data['description']
                : null,
            'base_capacity_adults' => max(1, (int) $data['base_capacity_adults']),
            'base_capacity_children' => max(0, (int) $data['base_capacity_children']),
            'base_rate' => number_format((float) $baseRate, 2, '.', ''),
            'extra_bed_rate' => ($extraBed === null || $extraBed === '')
                ? null
                : number_format((float) $extraBed, 2, '.', ''),
            'amenities' => $this->service->encodeAmenities($amenityList),
        ];
    }
}
