<?php

declare(strict_types=1);

namespace App\Core;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;

use function FastRoute\simpleDispatcher;

final class Router
{
    private Dispatcher $dispatcher;

    public function __construct(
        private readonly Container $container,
        private readonly Auth $auth,
        private readonly Session $session,
    ) {}

    public function loadRoutes(string $routeFile): void
    {
        $auth = $this->auth;
        $this->dispatcher = simpleDispatcher(function (RouteCollector $r) use ($routeFile, $auth) {
            require $routeFile;
        });
    }

    public function dispatch(string $method, string $uri): void
    {
        // Удаляем query string из URI
        $uri = rawurldecode(parse_url($uri, PHP_URL_PATH) ?? '/');

        $routeInfo = $this->dispatcher->dispatch($method, $uri);

        match ($routeInfo[0]) {
            Dispatcher::NOT_FOUND => $this->sendError(404, 'Страница не найдена'),
            Dispatcher::METHOD_NOT_ALLOWED => $this->sendError(405, 'Метод не разрешён'),
            Dispatcher::FOUND => $this->handleRoute($routeInfo[1], $routeInfo[2]),
        };
    }

    private function handleRoute(array $handler, array $vars): void
    {
        [$controllerClass, $method, $middleware] = array_pad($handler, 3, []);

        // Проверка middleware
        if (is_array($middleware)) {
            foreach ($middleware as $mw) {
                if ($mw === 'auth' && !$this->auth->check()) {
                    self::redirect('/login');
                    return;
                }

                if (str_starts_with($mw, 'role:')) {
                    $requiredRole = substr($mw, 5);
                    if (!$this->auth->check()) {
                        self::redirect('/login');
                        return;
                    }
                    if ($this->auth->user()['role'] !== $requiredRole) {
                        $this->sendError(403, 'Доступ запрещён');
                        return;
                    }
                }

                if ($mw === 'csrf') {
                    $token = $_POST['_csrf_token'] ?? '';
                    if (!$this->session->validateCsrfToken($token)) {
                        $this->sendError(403, 'Недопустимый CSRF-токен. Обновите страницу и попробуйте снова.');
                        return;
                    }
                }
            }
        }

        $controller = $this->container->get($controllerClass);
        $controller->$method(...$vars);
    }

    private function sendError(int $code, string $message): void
    {
        http_response_code($code);
        $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        echo "<h1>{$code}</h1><p>{$safeMessage}</p>";
    }

    public static function redirect(string $url, int $code = 302): void
    {
        // Защита от Open Redirect: разрешаем только относительные пути
        if (!str_starts_with($url, '/') || str_starts_with($url, '//')) {
            $url = '/';
        }

        http_response_code($code);
        header("Location: {$url}");
        exit;
    }
}
