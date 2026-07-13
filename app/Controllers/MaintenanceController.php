<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Models\MaintenanceRequest;
use App\Models\Room;
use App\Models\Staff;
use App\Services\MaintenanceService;
use App\Services\RoomService;

final class MaintenanceController
{
    private MaintenanceRequest $requests;
    private MaintenanceService $service;
    private Room $rooms;
    private Staff $staff;
    private RoomService $roomService;

    public function __construct(
        ?MaintenanceRequest $requests = null,
        ?MaintenanceService $service = null,
        ?Room $rooms = null,
        ?Staff $staff = null,
        ?RoomService $roomService = null,
    ) {
        $this->requests = $requests ?? new MaintenanceRequest();
        $this->service = $service ?? new MaintenanceService();
        $this->rooms = $rooms ?? new Room();
        $this->staff = $staff ?? new Staff();
        $this->roomService = $roomService ?? new RoomService();
    }

    public function index(Request $request): void
    {
        Auth::requirePermission(\Permission::MAINTENANCE_VIEW);

        $filters = [
            'status' => $this->stringOrNull($request->input('status')),
            'priority' => $this->stringOrNull($request->input('priority')),
            'q' => $this->stringOrNull($request->input('q')),
        ];

        View::render('maintenance/index', [
            'title' => 'Maintenance',
            'requests' => $this->requests->filtered($filters),
            'counts' => $this->requests->statusCounts(),
            'filters' => $filters,
            'canManage' => Auth::can(\Permission::MAINTENANCE_MANAGE),
            'maintenanceService' => $this->service,
            'roomService' => $this->roomService,
        ], 'app');
    }

    public function create(Request $request): void
    {
        Auth::requirePermission(\Permission::MAINTENANCE_MANAGE);

        View::render('maintenance/form', [
            'title' => 'New maintenance request',
            'rooms' => $this->rooms->filtered([]),
            'staffList' => $this->staff->activeList(),
            'errors' => Session::pullFlash('errors') ?? [],
            'old' => Session::pullFlash('old') ?? [],
            'maintenanceService' => $this->service,
            'prefillRoomId' => (int) ($request->input('room_id') ?? 0),
        ], 'app');
    }

    public function store(Request $request): void
    {
        Auth::requirePermission(\Permission::MAINTENANCE_MANAGE);

        $validator = new Validator();
        $data = $validator->validate($request->post(), [
            'issue_title' => 'required|max:150',
            'description' => 'nullable|max:2000',
            'priority' => 'required',
            'room_id' => 'nullable|int',
            'assigned_to' => 'nullable|int',
        ]);

        if ($data === null) {
            Session::flash('errors', $validator->firstErrors());
            Session::flash('old', $request->post());
            redirect('/maintenance/create');
        }

        $result = $this->service->create([
            'issue_title' => (string) $data['issue_title'],
            'description' => $data['description'] ?? null,
            'priority' => (string) $data['priority'],
            'room_id' => $data['room_id'] ?? null,
            'assigned_to' => $data['assigned_to'] ?? null,
        ], Auth::id());

        if (!$result['ok']) {
            Session::flash('error', $result['error']);
            Session::flash('old', $request->post());
            redirect('/maintenance/create');
        }

        Session::flash('success', 'Maintenance request created.');
        redirect('/maintenance/' . $result['id']);
    }

    public function show(Request $request, string $id): void
    {
        Auth::requirePermission(\Permission::MAINTENANCE_VIEW);

        $requestRow = $this->requests->findById((int) $id);
        if ($requestRow === null) {
            Session::flash('error', 'Request not found.');
            redirect('/maintenance');
        }

        View::render('maintenance/show', [
            'title' => (string) $requestRow['issue_title'],
            'request' => $requestRow,
            'staffList' => $this->staff->activeList(),
            'canManage' => Auth::can(\Permission::MAINTENANCE_MANAGE),
            'maintenanceService' => $this->service,
            'roomService' => $this->roomService,
        ], 'app');
    }

    public function assign(Request $request, string $id): void
    {
        Auth::requirePermission(\Permission::MAINTENANCE_MANAGE);

        $assigned = $request->input('assigned_to');
        $staffId = ($assigned === null || $assigned === '') ? null : (int) $assigned;

        $result = $this->service->assign((int) $id, $staffId);
        Session::flash($result['ok'] ? 'success' : 'error', $result['ok'] ? 'Assignee updated.' : $result['error']);
        redirect('/maintenance/' . (int) $id);
    }

    public function start(Request $request, string $id): void
    {
        Auth::requirePermission(\Permission::MAINTENANCE_MANAGE);

        $result = $this->service->start((int) $id, Auth::id());
        Session::flash($result['ok'] ? 'success' : 'error', $result['ok'] ? 'Work started.' : $result['error']);
        redirect('/maintenance/' . (int) $id);
    }

    public function resolve(Request $request, string $id): void
    {
        Auth::requirePermission(\Permission::MAINTENANCE_MANAGE);

        $result = $this->service->resolve((int) $id, Auth::id());
        if (!$result['ok']) {
            Session::flash('error', $result['error']);
        } else {
            $msg = 'Request resolved.';
            if (!empty($result['room_released'])) {
                $msg .= ' Room returned to inventory.';
            }
            Session::flash('success', $msg);
        }
        redirect('/maintenance/' . (int) $id);
    }

    public function cancel(Request $request, string $id): void
    {
        Auth::requirePermission(\Permission::MAINTENANCE_MANAGE);

        $result = $this->service->cancel((int) $id, Auth::id());
        if (!$result['ok']) {
            Session::flash('error', $result['error']);
        } else {
            $msg = 'Request cancelled.';
            if (!empty($result['room_released'])) {
                $msg .= ' Room returned to inventory.';
            }
            Session::flash('success', $msg);
        }
        redirect('/maintenance/' . (int) $id);
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
