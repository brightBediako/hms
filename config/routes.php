<?php

declare(strict_types=1);

use App\Controllers\HealthController;
use App\Core\Router;

/** @var Router $router */
$router->get('/', [HealthController::class, 'home']);
$router->get('/health', [HealthController::class, 'ping']);
$router->get('/ping', [HealthController::class, 'ping']);
