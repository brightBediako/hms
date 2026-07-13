<?php

declare(strict_types=1);

/**
 * Permission key constants — match db/hms_seed_data.sql.
 * Auth::can() should use these keys, never role name strings.
 */

final class Permission
{
    public const DASHBOARD_VIEW = 'dashboard.view';

    public const RESERVATIONS_VIEW = 'reservations.view';
    public const RESERVATIONS_CREATE = 'reservations.create';
    public const RESERVATIONS_EDIT = 'reservations.edit';
    public const RESERVATIONS_CANCEL = 'reservations.cancel';

    public const FRONTDESK_CHECKIN = 'frontdesk.checkin';
    public const FRONTDESK_CHECKOUT = 'frontdesk.checkout';
    public const FRONTDESK_TRANSFER = 'frontdesk.transfer';

    public const ROOMS_VIEW = 'rooms.view';
    public const ROOMS_MANAGE = 'rooms.manage';

    public const GUESTS_VIEW = 'guests.view';
    public const GUESTS_MANAGE = 'guests.manage';

    public const BILLING_VIEW = 'billing.view';
    public const BILLING_CREATE = 'billing.create';
    public const BILLING_VOID = 'billing.void';

    public const PAYMENTS_RECORD = 'payments.record';

    public const HOUSEKEEPING_VIEW = 'housekeeping.view';
    public const HOUSEKEEPING_MANAGE = 'housekeeping.manage';

    public const MAINTENANCE_VIEW = 'maintenance.view';
    public const MAINTENANCE_MANAGE = 'maintenance.manage';

    public const EXPENSES_VIEW = 'expenses.view';
    public const EXPENSES_MANAGE = 'expenses.manage';

    public const STAFF_MANAGE = 'staff.manage';
    public const REPORTS_VIEW = 'reports.view';
    public const AUDIT_VIEW = 'audit.view';
    public const SETTINGS_MANAGE = 'settings.manage';
    public const BACKUP_MANAGE = 'backup.manage';

    /** @return list<string> */
    public static function all(): array
    {
        return [
            self::DASHBOARD_VIEW,
            self::RESERVATIONS_VIEW,
            self::RESERVATIONS_CREATE,
            self::RESERVATIONS_EDIT,
            self::RESERVATIONS_CANCEL,
            self::FRONTDESK_CHECKIN,
            self::FRONTDESK_CHECKOUT,
            self::FRONTDESK_TRANSFER,
            self::ROOMS_VIEW,
            self::ROOMS_MANAGE,
            self::GUESTS_VIEW,
            self::GUESTS_MANAGE,
            self::BILLING_VIEW,
            self::BILLING_CREATE,
            self::BILLING_VOID,
            self::PAYMENTS_RECORD,
            self::HOUSEKEEPING_VIEW,
            self::HOUSEKEEPING_MANAGE,
            self::MAINTENANCE_VIEW,
            self::MAINTENANCE_MANAGE,
            self::EXPENSES_VIEW,
            self::EXPENSES_MANAGE,
            self::STAFF_MANAGE,
            self::REPORTS_VIEW,
            self::AUDIT_VIEW,
            self::SETTINGS_MANAGE,
            self::BACKUP_MANAGE,
        ];
    }
}
