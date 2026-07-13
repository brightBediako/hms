<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Models\HousekeepingTask;
use App\Models\Room;
use App\Models\Staff;
use App\Services\HousekeepingService;
use App\Services\RoomService;

final class HousekeepingController
{
    private HousekeepingTask $tasks;
    private HousekeepingService $service;
    private Room $rooms;
    private Staff $staff;
    private RoomService $roomService;

    public function __construct(
        ?HousekeepingTask $tasks = null,
        ?HousekeepingService $service = null,
        ?Room $rooms = null,
        ?Staff $staff = null,
        ?RoomService $roomService = null,
    ) {
        $this->tasks = $tasks ?? new HousekeepingTask();
        $this->service = $service ?? new HousekeepingService();
        $this->rooms = $rooms ?? new Room();
        $this->staff = $staff ?? new Staff();
        $this->roomService = $roomService ?? new RoomService();
    }

    public function index(Request $request): void
    {
        Auth::requirePermission(\Permission::HOUSEKEEPING_VIEW);

        $date = $this->dateOrToday($request->input('date'));
        $filters = [
            'status' => $this->stringOrNull($request->input('status')),
            'task_type' => $this->stringOrNull($request->input('task_type')),
            'scheduled_for' => $request->input('all_dates') === '1' ? null : $date,
            'q' => $this->stringOrNull($request->input('q')),
            'assigned_to' => $request->input('assigned_to'),
        ];

        // When "all dates" not set, still show overdue open tasks for today filter via scheduled_for
        $taskFilters = $filters;
        if ($filters['scheduled_for'] !== null) {
            // Include open tasks scheduled on or before date
            unset($taskFilters['scheduled_for']);
        }

        $tasks = $this->tasks->filtered($taskFilters);
        if ($filters['scheduled_for'] !== null) {
            $tasks = array_values(array_filter(
                $tasks,
                static function (array $row) use ($date): bool {
                    $scheduled = $row['scheduled_for'] ?? null;
                    if ($scheduled === null || $scheduled === '') {
                        return true;
                    }
                    if (in_array((string) $row['status'], HousekeepingTask::OPEN_STATUSES, true)) {
                        return (string) $scheduled <= $date;
                    }

                    return (string) $scheduled === $date;
                }
            ));
        }

        View::render('housekeeping/index', [
            'title' => 'Housekeeping',
            'date' => $date,
            'allDates' => $request->input('all_dates') === '1',
            'tasks' => $tasks,
            'counts' => $this->tasks->statusCounts($filters['scheduled_for']),
            'filters' => $filters,
            'staffList' => $this->staff->activeList(),
            'canManage' => Auth::can(\Permission::HOUSEKEEPING_MANAGE),
            'hkService' => $this->service,
            'roomService' => $this->roomService,
        ], 'app');
    }

    public function create(Request $request): void
    {
        Auth::requirePermission(\Permission::HOUSEKEEPING_MANAGE);

        View::render('housekeeping/form', [
            'title' => 'New housekeeping task',
            'rooms' => $this->rooms->filtered([]),
            'staffList' => $this->staff->activeList(),
            'errors' => Session::pullFlash('errors') ?? [],
            'old' => Session::pullFlash('old') ?? [],
            'hkService' => $this->service,
        ], 'app');
    }

    public function store(Request $request): void
    {
        Auth::requirePermission(\Permission::HOUSEKEEPING_MANAGE);

        $validator = new Validator();
        $data = $validator->validate($request->post(), [
            'room_id' => 'required|int',
            'task_type' => 'required',
            'scheduled_for' => 'nullable|date',
            'assigned_to' => 'nullable|int',
            'notes' => 'nullable|max:255',
        ]);

        if ($data === null) {
            Session::flash('errors', $validator->firstErrors());
            Session::flash('old', $request->post());
            redirect('/housekeeping/create');
        }

        $result = $this->service->create([
            'room_id' => (int) $data['room_id'],
            'task_type' => (string) $data['task_type'],
            'scheduled_for' => $data['scheduled_for'] ?? date('Y-m-d'),
            'assigned_to' => $data['assigned_to'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        if (!$result['ok']) {
            Session::flash('error', $result['error']);
            Session::flash('old', $request->post());
            redirect('/housekeeping/create');
        }

        Session::flash('success', 'Housekeeping task created.');
        redirect('/housekeeping/' . $result['id']);
    }

    public function show(Request $request, string $id): void
    {
        Auth::requirePermission(\Permission::HOUSEKEEPING_VIEW);

        $task = $this->tasks->findById((int) $id);
        if ($task === null) {
            Session::flash('error', 'Task not found.');
            redirect('/housekeeping');
        }

        View::render('housekeeping/show', [
            'title' => 'Room #' . $task['room_number'] . ' clean',
            'task' => $task,
            'staffList' => $this->staff->activeList(),
            'canManage' => Auth::can(\Permission::HOUSEKEEPING_MANAGE),
            'hkService' => $this->service,
            'roomService' => $this->roomService,
        ], 'app');
    }

    public function assign(Request $request, string $id): void
    {
        Auth::requirePermission(\Permission::HOUSEKEEPING_MANAGE);

        $assigned = $request->input('assigned_to');
        $staffId = ($assigned === null || $assigned === '') ? null : (int) $assigned;

        $result = $this->service->assign((int) $id, $staffId);
        if (!$result['ok']) {
            Session::flash('error', $result['error']);
        } else {
            Session::flash('success', 'Assignee updated.');
        }

        redirect('/housekeeping/' . (int) $id);
    }

    public function start(Request $request, string $id): void
    {
        Auth::requirePermission(\Permission::HOUSEKEEPING_MANAGE);

        $result = $this->service->start((int) $id);
        if (!$result['ok']) {
            Session::flash('error', $result['error']);
        } else {
            Session::flash('success', 'Task started.');
        }

        redirect('/housekeeping/' . (int) $id);
    }

    public function complete(Request $request, string $id): void
    {
        Auth::requirePermission(\Permission::HOUSEKEEPING_MANAGE);

        $result = $this->service->complete((int) $id, Auth::id());
        if (!$result['ok']) {
            Session::flash('error', $result['error']);
        } else {
            $msg = 'Task completed.';
            if (!empty($result['room_released'])) {
                $msg .= ' Room set to Available.';
            }
            Session::flash('success', $msg);
        }

        redirect('/housekeeping/' . (int) $id);
    }

    public function verify(Request $request, string $id): void
    {
        Auth::requirePermission(\Permission::HOUSEKEEPING_MANAGE);

        $result = $this->service->verify((int) $id, Auth::id());
        if (!$result['ok']) {
            Session::flash('error', $result['error']);
        } else {
            $msg = 'Task verified.';
            if (!empty($result['room_released'])) {
                $msg .= ' Room set to Available.';
            }
            Session::flash('success', $msg);
        }

        redirect('/housekeeping/' . (int) $id);
    }

    private function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $str = trim((string) $value);

        return $str === '' ? null : $str;
    }

    private function dateOrToday(mixed $value): string
    {
        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            return $value;
        }

        return date('Y-m-d');
    }
}
