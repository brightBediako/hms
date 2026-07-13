<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\HealthController;
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
