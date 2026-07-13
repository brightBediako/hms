<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\GuestController;
use App\Controllers\HealthController;
use App\Controllers\ReservationController;
use App\Controllers\RoomController;
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
$router->get('/dashboard', [AuthController::class, 'dashboard'], [
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
