<?php

declare(strict_types=1);

/**
 * Sidebar navigation — filtered by Auth::can(permission) at render time.
 * `ready` false = placeholder until that module is built.
 */

return [
    'operations' => [
        [
            'label' => 'Dashboard',
            'icon' => 'dashboard',
            'path' => '/dashboard',
            'permission' => 'dashboard.view',
            'ready' => true,
        ],
        [
            'label' => 'Reservations',
            'icon' => 'calendar_month',
            'path' => '/reservations',
            'permission' => 'reservations.view',
            'ready' => false,
        ],
        [
            'label' => 'Front Desk',
            'icon' => 'desk',
            'path' => '/frontdesk',
            'permission' => 'frontdesk.checkin',
            'ready' => false,
        ],
        [
            'label' => 'Rooms',
            'icon' => 'bed',
            'path' => '/rooms',
            'permission' => 'rooms.view',
            'ready' => false,
        ],
        [
            'label' => 'Guests',
            'icon' => 'group',
            'path' => '/guests',
            'permission' => 'guests.view',
            'ready' => false,
        ],
        [
            'label' => 'Billing',
            'icon' => 'payments',
            'path' => '/billing',
            'permission' => 'billing.view',
            'ready' => false,
        ],
        [
            'label' => 'Payments',
            'icon' => 'account_balance_wallet',
            'path' => '/payments',
            'permission' => 'payments.record',
            'ready' => false,
        ],
        [
            'label' => 'Housekeeping',
            'icon' => 'cleaning_services',
            'path' => '/housekeeping',
            'permission' => 'housekeeping.view',
            'ready' => false,
        ],
    ],
    'administration' => [
        [
            'label' => 'Maintenance',
            'icon' => 'build',
            'path' => '/maintenance',
            'permission' => 'maintenance.view',
            'ready' => false,
        ],
        [
            'label' => 'Expenses',
            'icon' => 'receipt_long',
            'path' => '/expenses',
            'permission' => 'expenses.view',
            'ready' => false,
        ],
        [
            'label' => 'Staff',
            'icon' => 'badge',
            'path' => '/staff',
            'permission' => 'staff.manage',
            'ready' => false,
        ],
        [
            'label' => 'Reports',
            'icon' => 'analytics',
            'path' => '/reports',
            'permission' => 'reports.view',
            'ready' => false,
        ],
        [
            'label' => 'Notifications',
            'icon' => 'notifications',
            'path' => '/notifications',
            'permission' => 'dashboard.view',
            'ready' => false,
        ],
        [
            'label' => 'Audit Logs',
            'icon' => 'history',
            'path' => '/audit',
            'permission' => 'audit.view',
            'ready' => false,
        ],
        [
            'label' => 'Settings',
            'icon' => 'settings',
            'path' => '/settings',
            'permission' => 'settings.manage',
            'ready' => false,
        ],
    ],
];
