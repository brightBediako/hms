<?php

declare(strict_types=1);

/**
 * Default role → permission key map (mirrors db/hms_seed_data.sql).
 * Runtime authorization uses Auth::can() with keys loaded from the database
 * at login — this file is the documented baseline, not a second source of truth.
 */

return [
    'Owner' => ['*'],

    'Manager' => [
        'dashboard.view',
        'reservations.view', 'reservations.create', 'reservations.edit', 'reservations.cancel',
        'frontdesk.checkin', 'frontdesk.checkout', 'frontdesk.transfer',
        'rooms.view', 'rooms.manage',
        'guests.view', 'guests.manage',
        'billing.view', 'billing.create', 'billing.void',
        'payments.record',
        'housekeeping.view', 'housekeeping.manage',
        'maintenance.view', 'maintenance.manage',
        'expenses.view', 'expenses.manage',
        'reports.view',
        'audit.view',
    ],

    'Receptionist' => [
        'dashboard.view',
        'reservations.view', 'reservations.create', 'reservations.edit', 'reservations.cancel',
        'frontdesk.checkin', 'frontdesk.checkout', 'frontdesk.transfer',
        'rooms.view',
        'guests.view', 'guests.manage',
        'billing.view', 'billing.create',
        'payments.record',
    ],

    'Accountant' => [
        'dashboard.view',
        'billing.view', 'billing.create', 'billing.void',
        'payments.record',
        'expenses.view', 'expenses.manage',
        'reports.view',
    ],

    'Housekeeping Staff' => [
        'housekeeping.view', 'housekeeping.manage',
        'rooms.view',
    ],

    'Maintenance Staff' => [
        'maintenance.view', 'maintenance.manage',
        'rooms.view',
    ],

    'System Administrator' => [
        'dashboard.view',
        'staff.manage',
        'settings.manage',
        'backup.manage',
        'audit.view',
    ],
];
