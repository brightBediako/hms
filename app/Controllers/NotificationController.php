<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Core\View;
use App\Services\NotificationService;

final class NotificationController
{
    private NotificationService $service;

    public function __construct(?NotificationService $service = null)
    {
        $this->service = $service ?? new NotificationService();
    }

    public function index(Request $request): void
    {
        Auth::requirePermission(\Permission::DASHBOARD_VIEW);

        $staffId = Auth::id();
        if ($staffId === null) {
            redirect('/login');
        }

        $filter = $this->stringOrNull($request->input('filter'));
        if ($filter !== null && !in_array($filter, ['unread', 'read'], true)) {
            $filter = null;
        }

        View::render('notifications/index', [
            'title' => 'Notifications',
            'notifications' => $this->service->listForStaff($staffId, $filter),
            'unreadCount' => $this->service->unreadCount($staffId),
            'filter' => $filter,
            'notificationService' => $this->service,
        ], 'app');
    }

    public function markRead(Request $request, string $id): void
    {
        Auth::requirePermission(\Permission::DASHBOARD_VIEW);

        $staffId = Auth::id();
        if ($staffId === null) {
            redirect('/login');
        }

        $ok = $this->service->markRead((int) $id, $staffId);
        Session::flash($ok ? 'success' : 'error', $ok ? 'Marked as read.' : 'Notification not found.');
        redirect('/notifications');
    }

    public function markAllRead(Request $request): void
    {
        Auth::requirePermission(\Permission::DASHBOARD_VIEW);

        $staffId = Auth::id();
        if ($staffId === null) {
            redirect('/login');
        }

        $count = $this->service->markAllRead($staffId);
        Session::flash('success', $count > 0 ? "Marked {$count} notification(s) as read." : 'No unread notifications.');
        redirect('/notifications');
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
