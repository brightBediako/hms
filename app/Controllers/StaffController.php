<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Models\Role;
use App\Models\Staff;
use App\Services\StaffService;

final class StaffController
{
    private Staff $staff;
    private Role $roles;
    private StaffService $service;

    public function __construct(
        ?Staff $staff = null,
        ?Role $roles = null,
        ?StaffService $service = null,
    ) {
        $this->staff = $staff ?? new Staff();
        $this->roles = $roles ?? new Role();
        $this->service = $service ?? new StaffService();
    }

    public function index(Request $request): void
    {
        Auth::requirePermission(\Permission::STAFF_MANAGE);

        $filters = [
            'status' => $this->stringOrNull($request->input('status')),
            'role_id' => $this->intOrNull($request->input('role_id')),
            'q' => $this->stringOrNull($request->input('q')),
        ];

        View::render('staff/index', [
            'title' => 'Staff',
            'staffList' => $this->staff->filtered($filters),
            'roles' => $this->roles->all(),
            'filters' => $filters,
            'staffService' => $this->service,
        ], 'app');
    }

    public function create(Request $request): void
    {
        Auth::requirePermission(\Permission::STAFF_MANAGE);

        View::render('staff/form', [
            'title' => 'New staff account',
            'member' => null,
            'roles' => $this->roles->all(),
            'errors' => Session::pullFlash('errors') ?? [],
            'old' => Session::pullFlash('old') ?? [],
            'staffService' => $this->service,
        ], 'app');
    }

    public function store(Request $request): void
    {
        Auth::requirePermission(\Permission::STAFF_MANAGE);

        $validator = new Validator();
        $data = $validator->validate($request->post(), [
            'full_name' => 'required|max:100',
            'email' => 'required|email|max:150',
            'phone' => 'nullable|max:30',
            'role_id' => 'required|int',
            'status' => 'required',
            'password' => 'required|min:8|max:100',
        ]);

        if ($data === null) {
            Session::flash('errors', $validator->firstErrors());
            Session::flash('old', $request->post());
            redirect('/staff/create');
        }

        $result = $this->service->create([
            'full_name' => (string) $data['full_name'],
            'email' => (string) $data['email'],
            'phone' => $data['phone'] ?? null,
            'role_id' => (int) $data['role_id'],
            'status' => (string) $data['status'],
            'password' => (string) $data['password'],
        ]);

        if (!$result['ok']) {
            Session::flash('error', $result['error']);
            Session::flash('old', $request->post());
            redirect('/staff/create');
        }

        Session::flash('success', 'Staff account created.');
        redirect('/staff/' . $result['id']);
    }

    public function show(Request $request, string $id): void
    {
        Auth::requirePermission(\Permission::STAFF_MANAGE);

        $member = $this->staff->findById((int) $id);
        if ($member === null) {
            Session::flash('error', 'Staff member not found.');
            redirect('/staff');
        }

        View::render('staff/show', [
            'title' => (string) $member['full_name'],
            'member' => $member,
            'permissions' => $this->roles->permissions((int) $member['role_id']),
            'staffService' => $this->service,
            'isSelf' => Auth::id() === (int) $member['id'],
        ], 'app');
    }

    public function edit(Request $request, string $id): void
    {
        Auth::requirePermission(\Permission::STAFF_MANAGE);

        $member = $this->staff->findById((int) $id);
        if ($member === null) {
            Session::flash('error', 'Staff member not found.');
            redirect('/staff');
        }

        View::render('staff/form', [
            'title' => 'Edit staff account',
            'member' => $member,
            'roles' => $this->roles->all(),
            'errors' => Session::pullFlash('errors') ?? [],
            'old' => Session::pullFlash('old') ?? [],
            'staffService' => $this->service,
        ], 'app');
    }

    public function update(Request $request, string $id): void
    {
        Auth::requirePermission(\Permission::STAFF_MANAGE);

        $staffId = (int) $id;
        if ($this->staff->findById($staffId) === null) {
            Session::flash('error', 'Staff member not found.');
            redirect('/staff');
        }

        $validator = new Validator();
        $data = $validator->validate($request->post(), [
            'full_name' => 'required|max:100',
            'email' => 'required|email|max:150',
            'phone' => 'nullable|max:30',
            'role_id' => 'required|int',
            'status' => 'required',
            'password' => 'nullable|min:8|max:100',
        ]);

        if ($data === null) {
            Session::flash('errors', $validator->firstErrors());
            Session::flash('old', $request->post());
            redirect('/staff/' . $staffId . '/edit');
        }

        $result = $this->service->update($staffId, [
            'full_name' => (string) $data['full_name'],
            'email' => (string) $data['email'],
            'phone' => $data['phone'] ?? null,
            'role_id' => (int) $data['role_id'],
            'status' => (string) $data['status'],
            'password' => $data['password'] ?? null,
        ], Auth::id());

        if (!$result['ok']) {
            Session::flash('error', $result['error']);
            Session::flash('old', $request->post());
            redirect('/staff/' . $staffId . '/edit');
        }

        Session::flash('success', 'Staff account updated.');
        redirect('/staff/' . $staffId);
    }

    private function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $str = trim((string) $value);

        return $str === '' ? null : $str;
    }

    private function intOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
