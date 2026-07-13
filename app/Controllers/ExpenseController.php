<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Services\ExpenseService;

final class ExpenseController
{
    private Expense $expenses;
    private ExpenseCategory $categories;
    private ExpenseService $service;

    public function __construct(
        ?Expense $expenses = null,
        ?ExpenseCategory $categories = null,
        ?ExpenseService $service = null,
    ) {
        $this->expenses = $expenses ?? new Expense();
        $this->categories = $categories ?? new ExpenseCategory();
        $this->service = $service ?? new ExpenseService();
    }

    public function index(Request $request): void
    {
        Auth::requirePermission(\Permission::EXPENSES_VIEW);

        $filters = [
            'category_id' => $this->intOrNull($request->input('category_id')),
            'date_from' => $this->stringOrNull($request->input('date_from')),
            'date_to' => $this->stringOrNull($request->input('date_to')),
            'q' => $this->stringOrNull($request->input('q')),
        ];

        View::render('expenses/index', [
            'title' => 'Expenses',
            'expenses' => $this->expenses->filtered($filters),
            'totalAmount' => $this->expenses->sumFiltered($filters),
            'categories' => $this->categories->all(),
            'filters' => $filters,
            'canManage' => Auth::can(\Permission::EXPENSES_MANAGE),
        ], 'app');
    }

    public function create(Request $request): void
    {
        Auth::requirePermission(\Permission::EXPENSES_MANAGE);

        View::render('expenses/form', [
            'title' => 'Record expense',
            'categories' => $this->categories->all(),
            'errors' => Session::pullFlash('errors') ?? [],
            'old' => Session::pullFlash('old') ?? [],
        ], 'app');
    }

    public function store(Request $request): void
    {
        Auth::requirePermission(\Permission::EXPENSES_MANAGE);

        $validator = new Validator();
        $data = $validator->validate($request->post(), [
            'category_id' => 'required|int',
            'description' => 'required|max:255',
            'amount' => 'required',
            'expense_date' => 'required|date',
        ]);

        if ($data === null) {
            Session::flash('errors', $validator->firstErrors());
            Session::flash('old', $request->post());
            redirect('/expenses/create');
        }

        $files = $request->files();
        $receipt = $files['receipt'] ?? null;

        $result = $this->service->record([
            'category_id' => (int) $data['category_id'],
            'description' => (string) $data['description'],
            'amount' => (string) $data['amount'],
            'expense_date' => (string) $data['expense_date'],
            'receipt' => is_array($receipt) ? $receipt : null,
        ], Auth::id());

        if (!$result['ok']) {
            Session::flash('error', $result['error']);
            Session::flash('old', $request->post());
            redirect('/expenses/create');
        }

        Session::flash('success', 'Expense recorded.');
        redirect('/expenses/' . $result['id']);
    }

    public function show(Request $request, string $id): void
    {
        Auth::requirePermission(\Permission::EXPENSES_VIEW);

        $row = $this->expenses->findById((int) $id);
        if ($row === null) {
            Session::flash('error', 'Expense not found.');
            redirect('/expenses');
        }

        View::render('expenses/show', [
            'title' => 'Expense',
            'expense' => $row,
            'canManage' => Auth::can(\Permission::EXPENSES_MANAGE),
        ], 'app');
    }

    public function destroy(Request $request, string $id): void
    {
        Auth::requirePermission(\Permission::EXPENSES_MANAGE);

        $result = $this->service->delete((int) $id);
        Session::flash($result['ok'] ? 'success' : 'error', $result['ok'] ? 'Expense deleted.' : $result['error']);
        redirect('/expenses');
    }

    public function storeCategory(Request $request): void
    {
        Auth::requirePermission(\Permission::EXPENSES_MANAGE);

        $validator = new Validator();
        $data = $validator->validate($request->post(), [
            'name' => 'required|max:80',
        ]);

        if ($data === null) {
            Session::flash('error', $validator->firstErrors()['name'] ?? 'Invalid category name.');
            redirect('/expenses/create');
        }

        $result = $this->service->createCategory(['name' => (string) $data['name']]);
        Session::flash($result['ok'] ? 'success' : 'error', $result['ok'] ? 'Category added.' : $result['error']);
        redirect('/expenses/create');
    }

    public function receipt(Request $request, string $id): void
    {
        Auth::requirePermission(\Permission::EXPENSES_VIEW);

        $row = $this->expenses->findById((int) $id);
        if ($row === null || empty($row['receipt_path'])) {
            Session::flash('error', 'Receipt not found.');
            redirect('/expenses');
        }

        $absolute = $this->service->absolutePath((string) $row['receipt_path']);
        if ($absolute === null || !is_file($absolute)) {
            Session::flash('error', 'Receipt file missing.');
            redirect('/expenses/' . (int) $id);
        }

        $mime = mime_content_type($absolute) ?: 'application/octet-stream';
        $filename = basename((string) $row['receipt_path']);

        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Content-Length: ' . (string) filesize($absolute));
        readfile($absolute);
        exit;
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
