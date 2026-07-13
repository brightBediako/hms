<?php

declare(strict_types=1);

use App\Controllers\AuditController;
use App\Controllers\AuthController;
use App\Controllers\BillingController;
use App\Controllers\ExpenseController;
use App\Controllers\FrontDeskController;
use App\Controllers\GuestController;
use App\Controllers\HealthController;
use App\Controllers\HousekeepingController;
use App\Controllers\MaintenanceController;
use App\Controllers\NotificationController;
use App\Controllers\PaymentController;
use App\Controllers\ReportController;
use App\Controllers\ReservationController;
use App\Controllers\RoomController;
use App\Controllers\SettingsController;
use App\Controllers\StaffController;
use App\Core\Router;

/** @var Router $router */

// Public
$router->get('/health', [HealthController::class, 'ping']);
$router->get('/ping', [HealthController::class, 'ping']);

// Guest auth
$router->get('/', [AuthController::class, 'showLogin'], ['guest' => true]);
$router->get('/login', [AuthController::class, 'showLogin'], ['guest' => true]);
$router->post('/login', [AuthController::class, 'login'], ['guest' => true, 'csrf' => true]);

// Authenticated
$router->get('/dashboard', [ReportController::class, 'dashboard'], [
    'auth' => true,
    'permission' => 'dashboard.view',
]);
$router->post('/logout', [AuthController::class, 'logout'], [
    'auth' => true,
    'csrf' => true,
]);

// Room types & rate plans (feature 05)
$router->get('/rooms/types', [RoomController::class, 'typesIndex'], ['auth' => true]);
$router->get('/rooms/types/create', [RoomController::class, 'typesCreate'], ['auth' => true]);
$router->post('/rooms/types', [RoomController::class, 'typesStore'], ['auth' => true, 'csrf' => true]);
$router->get('/rooms/types/{id}', [RoomController::class, 'typesShow'], ['auth' => true]);
$router->get('/rooms/types/{id}/edit', [RoomController::class, 'typesEdit'], ['auth' => true]);
$router->post('/rooms/types/{id}', [RoomController::class, 'typesUpdate'], ['auth' => true, 'csrf' => true]);
$router->post('/rooms/types/{id}/delete', [RoomController::class, 'typesDestroy'], ['auth' => true, 'csrf' => true]);
$router->post('/rooms/types/{id}/rates', [RoomController::class, 'rateStore'], ['auth' => true, 'csrf' => true]);
$router->post('/rooms/types/{id}/rates/{rateId}/delete', [RoomController::class, 'rateDestroy'], ['auth' => true, 'csrf' => true]);

// Room inventory (feature 06) — after /rooms/types* so {id} does not capture "types"
$router->get('/rooms', [RoomController::class, 'index'], ['auth' => true]);
$router->get('/rooms/create', [RoomController::class, 'create'], ['auth' => true]);
$router->post('/rooms', [RoomController::class, 'store'], ['auth' => true, 'csrf' => true]);
$router->post('/rooms/{id}', [RoomController::class, 'update'], ['auth' => true, 'csrf' => true]);

// Guests (feature 07)
$router->get('/guests', [GuestController::class, 'index'], ['auth' => true]);
$router->get('/guests/search', [GuestController::class, 'searchApi'], ['auth' => true]);
$router->get('/guests/create', [GuestController::class, 'create'], ['auth' => true]);
$router->post('/guests', [GuestController::class, 'store'], ['auth' => true, 'csrf' => true]);
$router->get('/guests/{id}', [GuestController::class, 'show'], ['auth' => true]);
$router->get('/guests/{id}/edit', [GuestController::class, 'edit'], ['auth' => true]);
$router->post('/guests/{id}', [GuestController::class, 'update'], ['auth' => true, 'csrf' => true]);
$router->post('/guests/{id}/documents', [GuestController::class, 'documentStore'], ['auth' => true, 'csrf' => true]);
$router->post('/guests/{id}/documents/{docId}/delete', [GuestController::class, 'documentDestroy'], ['auth' => true, 'csrf' => true]);
$router->get('/guests/{id}/documents/{docId}/download', [GuestController::class, 'documentDownload'], ['auth' => true]);

// Reservations (feature 08)
$router->get('/reservations', [ReservationController::class, 'index'], ['auth' => true]);
$router->get('/reservations/calendar', [ReservationController::class, 'calendar'], ['auth' => true]);
$router->get('/reservations/availability', [ReservationController::class, 'availability'], ['auth' => true]);
$router->get('/reservations/create', [ReservationController::class, 'create'], ['auth' => true]);
$router->post('/reservations', [ReservationController::class, 'store'], ['auth' => true, 'csrf' => true]);
$router->get('/reservations/{id}', [ReservationController::class, 'show'], ['auth' => true]);
$router->get('/reservations/{id}/edit', [ReservationController::class, 'edit'], ['auth' => true]);
$router->post('/reservations/{id}', [ReservationController::class, 'update'], ['auth' => true, 'csrf' => true]);
$router->post('/reservations/{id}/cancel', [ReservationController::class, 'cancel'], ['auth' => true, 'csrf' => true]);

// Front desk (feature 09)
$router->get('/frontdesk', [FrontDeskController::class, 'index'], ['auth' => true]);
$router->post('/frontdesk/{id}/check-in', [FrontDeskController::class, 'checkIn'], ['auth' => true, 'csrf' => true]);
$router->post('/frontdesk/{id}/check-out', [FrontDeskController::class, 'checkOut'], ['auth' => true, 'csrf' => true]);
$router->post('/frontdesk/{id}/assign', [FrontDeskController::class, 'assign'], ['auth' => true, 'csrf' => true]);
$router->post('/frontdesk/{id}/transfer', [FrontDeskController::class, 'transfer'], ['auth' => true, 'csrf' => true]);
$router->post('/frontdesk/{id}/extend', [FrontDeskController::class, 'extend'], ['auth' => true, 'csrf' => true]);

// Billing (feature 10)
$router->get('/billing', [BillingController::class, 'index'], ['auth' => true]);
$router->get('/billing/create', [BillingController::class, 'create'], ['auth' => true]);
$router->post('/billing', [BillingController::class, 'store'], ['auth' => true, 'csrf' => true]);
$router->get('/billing/{id}', [BillingController::class, 'show'], ['auth' => true]);
$router->get('/billing/{id}/print', [BillingController::class, 'printView'], ['auth' => true]);
$router->post('/billing/{id}/items', [BillingController::class, 'itemStore'], ['auth' => true, 'csrf' => true]);
$router->post('/billing/{id}/items/{itemId}/delete', [BillingController::class, 'itemDestroy'], ['auth' => true, 'csrf' => true]);
$router->post('/billing/{id}/issue', [BillingController::class, 'issue'], ['auth' => true, 'csrf' => true]);
$router->post('/billing/{id}/void', [BillingController::class, 'void'], ['auth' => true, 'csrf' => true]);

// Payments (feature 11)
$router->get('/payments', [PaymentController::class, 'index'], ['auth' => true]);
$router->get('/payments/create', [PaymentController::class, 'create'], ['auth' => true]);
$router->post('/payments', [PaymentController::class, 'store'], ['auth' => true, 'csrf' => true]);

// Housekeeping (feature 12)
$router->get('/housekeeping', [HousekeepingController::class, 'index'], ['auth' => true]);
$router->get('/housekeeping/create', [HousekeepingController::class, 'create'], ['auth' => true]);
$router->post('/housekeeping', [HousekeepingController::class, 'store'], ['auth' => true, 'csrf' => true]);
$router->get('/housekeeping/{id}', [HousekeepingController::class, 'show'], ['auth' => true]);
$router->post('/housekeeping/{id}/assign', [HousekeepingController::class, 'assign'], ['auth' => true, 'csrf' => true]);
$router->post('/housekeeping/{id}/start', [HousekeepingController::class, 'start'], ['auth' => true, 'csrf' => true]);
$router->post('/housekeeping/{id}/complete', [HousekeepingController::class, 'complete'], ['auth' => true, 'csrf' => true]);
$router->post('/housekeeping/{id}/verify', [HousekeepingController::class, 'verify'], ['auth' => true, 'csrf' => true]);

// Maintenance (feature 13)
$router->get('/maintenance', [MaintenanceController::class, 'index'], ['auth' => true]);
$router->get('/maintenance/create', [MaintenanceController::class, 'create'], ['auth' => true]);
$router->post('/maintenance', [MaintenanceController::class, 'store'], ['auth' => true, 'csrf' => true]);
$router->get('/maintenance/{id}', [MaintenanceController::class, 'show'], ['auth' => true]);
$router->post('/maintenance/{id}/assign', [MaintenanceController::class, 'assign'], ['auth' => true, 'csrf' => true]);
$router->post('/maintenance/{id}/start', [MaintenanceController::class, 'start'], ['auth' => true, 'csrf' => true]);
$router->post('/maintenance/{id}/resolve', [MaintenanceController::class, 'resolve'], ['auth' => true, 'csrf' => true]);
$router->post('/maintenance/{id}/cancel', [MaintenanceController::class, 'cancel'], ['auth' => true, 'csrf' => true]);

// Expenses (feature 14)
$router->get('/expenses', [ExpenseController::class, 'index'], ['auth' => true]);
$router->get('/expenses/create', [ExpenseController::class, 'create'], ['auth' => true]);
$router->post('/expenses', [ExpenseController::class, 'store'], ['auth' => true, 'csrf' => true]);
$router->post('/expenses/categories', [ExpenseController::class, 'storeCategory'], ['auth' => true, 'csrf' => true]);
$router->get('/expenses/{id}', [ExpenseController::class, 'show'], ['auth' => true]);
$router->get('/expenses/{id}/receipt', [ExpenseController::class, 'receipt'], ['auth' => true]);
$router->post('/expenses/{id}/delete', [ExpenseController::class, 'destroy'], ['auth' => true, 'csrf' => true]);

// Staff & roles (feature 15)
$router->get('/staff', [StaffController::class, 'index'], ['auth' => true]);
$router->get('/staff/create', [StaffController::class, 'create'], ['auth' => true]);
$router->post('/staff', [StaffController::class, 'store'], ['auth' => true, 'csrf' => true]);
$router->get('/staff/{id}', [StaffController::class, 'show'], ['auth' => true]);
$router->get('/staff/{id}/edit', [StaffController::class, 'edit'], ['auth' => true]);
$router->post('/staff/{id}', [StaffController::class, 'update'], ['auth' => true, 'csrf' => true]);

// Reports & dashboard (feature 16)
$router->get('/reports', [ReportController::class, 'index'], ['auth' => true]);
$router->get('/reports/occupancy', [ReportController::class, 'occupancy'], ['auth' => true]);
$router->get('/reports/revenue', [ReportController::class, 'revenue'], ['auth' => true]);
$router->get('/reports/reservations', [ReportController::class, 'reservations'], ['auth' => true]);
$router->get('/reports/guests', [ReportController::class, 'guests'], ['auth' => true]);
$router->get('/reports/expenses', [ReportController::class, 'expenses'], ['auth' => true]);
$router->get('/reports/profit', [ReportController::class, 'profit'], ['auth' => true]);

// Notifications (feature 17)
$router->get('/notifications', [NotificationController::class, 'index'], ['auth' => true]);
$router->post('/notifications/read-all', [NotificationController::class, 'markAllRead'], ['auth' => true, 'csrf' => true]);
$router->post('/notifications/{id}/read', [NotificationController::class, 'markRead'], ['auth' => true, 'csrf' => true]);

// Audit logs (feature 18)
$router->get('/audit', [AuditController::class, 'index'], ['auth' => true]);
$router->get('/audit/{id}', [AuditController::class, 'show'], ['auth' => true]);

// Settings & backups (features 19–20)
$router->get('/settings', [SettingsController::class, 'index'], ['auth' => true]);
$router->get('/settings/hotel', [SettingsController::class, 'hotel'], ['auth' => true]);
$router->post('/settings/hotel', [SettingsController::class, 'hotelUpdate'], ['auth' => true, 'csrf' => true]);
$router->get('/settings/backups', [SettingsController::class, 'backups'], ['auth' => true]);
$router->post('/settings/backups', [SettingsController::class, 'backupCreate'], ['auth' => true, 'csrf' => true]);
$router->get('/settings/backups/{filename}/download', [SettingsController::class, 'backupDownload'], ['auth' => true]);
$router->post('/settings/backups/{filename}/delete', [SettingsController::class, 'backupDelete'], ['auth' => true, 'csrf' => true]);
