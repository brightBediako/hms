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
            'ready' => true,
        ],
        [
            'label' => 'Front Desk',
            'icon' => 'desk',
            'path' => '/frontdesk',
            'permission' => 'frontdesk.checkin',
            'ready' => true,
        ],
        [
            'label' => 'Rooms',
            'icon' => 'bed',
            'path' => '/rooms',
            'permission' => 'rooms.view',
            'ready' => true,
        ],
        [
            'label' => 'Room Types',
            'icon' => 'king_bed',
            'path' => '/rooms/types',
            'permission' => 'rooms.view',
            'ready' => true,
        ],
        [
            'label' => 'Guests',
            'icon' => 'group',
            'path' => '/guests',
            'permission' => 'guests.view',
            'ready' => true,
        ],
        [
            'label' => 'Billing',
            'icon' => 'payments',
            'path' => '/billing',
            'permission' => 'billing.view',
            'ready' => true,
        ],
        [
            'label' => 'Payments',
            'icon' => 'account_balance_wallet',
            'path' => '/payments',
            'permission' => 'payments.record',
            'ready' => true,
        ],
        [
            'label' => 'Housekeeping',
            'icon' => 'cleaning_services',
            'path' => '/housekeeping',
            'permission' => 'housekeeping.view',
            'ready' => true,
        ],
    ],
    'administration' => [
        [
            'label' => 'Maintenance',
            'icon' => 'build',
            'path' => '/maintenance',
            'permission' => 'maintenance.view',
            'ready' => true,
        ],
        [
            'label' => 'Expenses',
            'icon' => 'receipt_long',
            'path' => '/expenses',
            'permission' => 'expenses.view',
            'ready' => true,
        ],
        [
            'label' => 'Staff',
            'icon' => 'badge',
            'path' => '/staff',
            'permission' => 'staff.manage',
            'ready' => true,
        ],
        [
            'label' => 'Reports',
            'icon' => 'analytics',
            'path' => '/reports',
            'permission' => 'reports.view',
            'ready' => true,
        ],
        [
            'label' => 'Notifications',
            'icon' => 'notifications',
            'path' => '/notifications',
            'permission' => 'dashboard.view',
            'ready' => true,
        ],
        [
            'label' => 'Audit Logs',
            'icon' => 'history',
            'path' => '/audit',
            'permission' => 'audit.view',
            'ready' => true,
        ],
        [
            'label' => 'Settings',
            'icon' => 'settings',
            'path' => '/settings',
            'permission' => 'settings.manage',
            'ready' => true,
        ],
    ],
];
