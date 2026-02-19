<?php

declare(strict_types=1);

use App\Controller\AuthController;
use App\Controller\DispatcherController;
use App\Controller\MasterController;
use App\Controller\RequestController;

/**
 * Определение маршрутов приложения.
 *
 * Формат handler: [ControllerClass, 'method', ['middleware1', 'middleware2']]
 *
 * @var \FastRoute\RouteCollector $r
 */

// -- Главная страница --
$r->addRoute('GET', '/', [AuthController::class, 'home', []]);

// -- Аутентификация --
$r->addRoute('GET', '/login', [AuthController::class, 'showLogin', []]);
$r->addRoute('POST', '/login', [AuthController::class, 'login', ['csrf']]);
$r->addRoute('POST', '/logout', [AuthController::class, 'logout', ['csrf', 'auth']]);

// -- Создание заявки (публичная страница) --
$r->addRoute('GET', '/requests/create', [RequestController::class, 'showCreate', []]);
$r->addRoute('POST', '/requests', [RequestController::class, 'store', ['csrf']]);

// -- Панель диспетчера --
$r->addRoute('GET', '/dispatcher', [DispatcherController::class, 'index', ['auth', 'role:dispatcher']]);
$r->addRoute('POST', '/dispatcher/assign', [DispatcherController::class, 'assign', ['csrf', 'auth', 'role:dispatcher']]);
$r->addRoute('POST', '/dispatcher/cancel', [DispatcherController::class, 'cancel', ['csrf', 'auth', 'role:dispatcher']]);

// -- Панель мастера --
$r->addRoute('GET', '/master', [MasterController::class, 'index', ['auth', 'role:master']]);
$r->addRoute('POST', '/master/take', [MasterController::class, 'takeIntoWork', ['csrf', 'auth', 'role:master']]);
$r->addRoute('POST', '/master/finish', [MasterController::class, 'finish', ['csrf', 'auth', 'role:master']]);
