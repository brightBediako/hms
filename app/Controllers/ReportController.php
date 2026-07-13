<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\View;
use App\Services\PaymentService;
use App\Services\ReportService;
use App\Services\ReservationService;

final class ReportController
{
    private ReportService $reports;
    private ReservationService $reservationService;
    private PaymentService $paymentService;

    public function __construct(
        ?ReportService $reports = null,
        ?ReservationService $reservationService = null,
        ?PaymentService $paymentService = null,
    ) {
        $this->reports = $reports ?? new ReportService();
        $this->reservationService = $reservationService ?? new ReservationService();
        $this->paymentService = $paymentService ?? new PaymentService();
    }

    public function dashboard(Request $request): void
    {
        Auth::requirePermission(\Permission::DASHBOARD_VIEW);

        $snapshot = $this->reports->dashboardSnapshot();

        View::render('dashboard/index', [
            'title' => 'Dashboard',
            'snapshot' => $snapshot,
            'reportService' => $this->reports,
            'reservationService' => $this->reservationService,
            'canReports' => Auth::can(\Permission::REPORTS_VIEW),
            'user' => Auth::user(),
        ], 'app');
    }

    public function index(Request $request): void
    {
        Auth::requirePermission(\Permission::REPORTS_VIEW);

        $from = $this->reports->defaultFrom();
        $to = $this->reports->defaultTo();
        $profit = $this->reports->profitSummary($from, $to);

        View::render('reports/index', [
            'title' => 'Reports',
            'from' => $from,
            'to' => $to,
            'profit' => $profit,
        ], 'app');
    }

    public function occupancy(Request $request): void
    {
        Auth::requirePermission(\Permission::REPORTS_VIEW);
        [$from, $to, $print] = $this->rangeAndPrint($request);
        $data = $this->reports->occupancyReport($from, $to);

        View::render('reports/occupancy', [
            'title' => 'Occupancy report',
            'data' => $data,
            'from' => $from,
            'to' => $to,
            'reportService' => $this->reports,
            'printMode' => $print,
        ], $print ? 'print' : 'app');
    }

    public function revenue(Request $request): void
    {
        Auth::requirePermission(\Permission::REPORTS_VIEW);
        [$from, $to, $print] = $this->rangeAndPrint($request);
        $data = $this->reports->revenueReport($from, $to);

        View::render('reports/revenue', [
            'title' => 'Revenue report',
            'data' => $data,
            'from' => $from,
            'to' => $to,
            'reportService' => $this->reports,
            'paymentService' => $this->paymentService,
            'printMode' => $print,
        ], $print ? 'print' : 'app');
    }

    public function reservations(Request $request): void
    {
        Auth::requirePermission(\Permission::REPORTS_VIEW);
        [$from, $to, $print] = $this->rangeAndPrint($request);
        $data = $this->reports->reservationsReport($from, $to);

        View::render('reports/reservations', [
            'title' => 'Reservations report',
            'data' => $data,
            'from' => $from,
            'to' => $to,
            'reportService' => $this->reports,
            'reservationService' => $this->reservationService,
            'printMode' => $print,
        ], $print ? 'print' : 'app');
    }

    public function guests(Request $request): void
    {
        Auth::requirePermission(\Permission::REPORTS_VIEW);
        [$from, $to, $print] = $this->rangeAndPrint($request);
        $data = $this->reports->guestsReport($from, $to);

        View::render('reports/guests', [
            'title' => 'Guests report',
            'data' => $data,
            'from' => $from,
            'to' => $to,
            'printMode' => $print,
        ], $print ? 'print' : 'app');
    }

    public function expenses(Request $request): void
    {
        Auth::requirePermission(\Permission::REPORTS_VIEW);
        [$from, $to, $print] = $this->rangeAndPrint($request);
        $data = $this->reports->expensesReport($from, $to);

        View::render('reports/expenses', [
            'title' => 'Expenses report',
            'data' => $data,
            'from' => $from,
            'to' => $to,
            'printMode' => $print,
        ], $print ? 'print' : 'app');
    }

    public function profit(Request $request): void
    {
        Auth::requirePermission(\Permission::REPORTS_VIEW);
        [$from, $to, $print] = $this->rangeAndPrint($request);
        $data = $this->reports->profitSummary($from, $to);

        View::render('reports/profit', [
            'title' => 'Profit summary',
            'data' => $data,
            'from' => $from,
            'to' => $to,
            'printMode' => $print,
        ], $print ? 'print' : 'app');
    }

    /**
     * @return array{0: string, 1: string, 2: bool}
     */
    private function rangeAndPrint(Request $request): array
    {
        $from = (string) ($request->input('from') ?: $this->reports->defaultFrom());
        $to = (string) ($request->input('to') ?: $this->reports->defaultTo());
        [$from, $to] = $this->reports->normalizeRange($from, $to);
        $print = (string) ($request->input('print') ?? '') === '1';

        return [$from, $to, $print];
    }
}
