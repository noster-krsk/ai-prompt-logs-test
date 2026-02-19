<?php

declare(strict_types=1);

namespace App\Core;

use App\Controller\AuthController;
use App\Controller\DispatcherController;
use App\Controller\MasterController;
use App\Controller\RequestController;
use App\Domain\StateMachine\RequestStateMachine;
use App\Repository\AuditLogRepository;
use App\Repository\RequestRepository;
use App\Repository\UserRepository;
use App\Service\AuditService;
use App\Service\AuthService;
use App\Service\RateLimiter;
use App\Service\RequestService;
use Dotenv\Dotenv;

final class App
{
    private Container $container;

    public function run(): void
    {
        $this->loadEnvironment();
        $this->container = new Container();
        $this->registerServices();

        /** @var Session $session */
        $session = $this->container->get(Session::class);
        $session->start();

        /** @var Router $router */
        $router = $this->container->get(Router::class);
        $router->loadRoutes(__DIR__ . '/../../config/routes.php');
        $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
    }

    private function loadEnvironment(): void
    {
        $envPath = dirname(__DIR__, 2);
        if (file_exists($envPath . '/.env')) {
            $dotenv = Dotenv::createImmutable($envPath);
            $dotenv->safeLoad();
        }
    }

    private function registerServices(): void
    {
        $c = $this->container;

        // -- Infrastructure --

        $c->singleton(Database::class, fn() => new Database(
            host: $_ENV['MYSQL_HOST'] ?? getenv('MYSQL_HOST') ?: 'mysql',
            dbname: $_ENV['MYSQL_DATABASE'] ?? getenv('MYSQL_DATABASE') ?: 'app_database',
            username: $_ENV['MYSQL_USER'] ?? getenv('MYSQL_USER') ?: 'app_user',
            password: $_ENV['MYSQL_PASSWORD'] ?? getenv('MYSQL_PASSWORD') ?: 'app_password',
            port: (int) ($_ENV['MYSQL_PORT'] ?? getenv('MYSQL_PORT') ?: 3306),
        ));

        $c->singleton(RedisClient::class, fn() => new RedisClient(
            host: $_ENV['REDIS_HOST'] ?? getenv('REDIS_HOST') ?: 'redis',
            port: (int) ($_ENV['REDIS_PORT'] ?? getenv('REDIS_PORT') ?: 6379),
            password: $_ENV['REDIS_PASSWORD'] ?? getenv('REDIS_PASSWORD') ?: '',
        ));

        $c->singleton(Session::class, fn() => new Session());

        $c->singleton(Auth::class, fn(Container $c) => new Auth(
            $c->get(Session::class)
        ));

        $c->singleton(View::class, fn(Container $c) => new View(
            auth: $c->get(Auth::class),
            session: $c->get(Session::class),
            viewsPath: dirname(__DIR__, 2) . '/views',
            cachePath: dirname(__DIR__, 2) . '/cache',
        ));

        $c->singleton(Router::class, fn(Container $c) => new Router(
            container: $c,
            auth: $c->get(Auth::class),
            session: $c->get(Session::class),
        ));

        // -- Repositories --

        $c->singleton(UserRepository::class, fn(Container $c) => new UserRepository(
            $c->get(Database::class)
        ));

        $c->singleton(RequestRepository::class, fn(Container $c) => new RequestRepository(
            $c->get(Database::class)
        ));

        $c->singleton(AuditLogRepository::class, fn(Container $c) => new AuditLogRepository(
            $c->get(Database::class)
        ));

        // -- Domain --

        $c->singleton(RequestStateMachine::class, fn() => new RequestStateMachine());

        // -- Services --

        $c->singleton(RateLimiter::class, fn(Container $c) => new RateLimiter(
            redis: $c->get(RedisClient::class),
        ));

        $c->singleton(AuthService::class, fn(Container $c) => new AuthService(
            userRepository: $c->get(UserRepository::class),
            auth: $c->get(Auth::class),
        ));

        $c->singleton(AuditService::class, fn(Container $c) => new AuditService(
            auditLogRepository: $c->get(AuditLogRepository::class),
        ));

        $c->singleton(RequestService::class, fn(Container $c) => new RequestService(
            db: $c->get(Database::class),
            requestRepository: $c->get(RequestRepository::class),
            userRepository: $c->get(UserRepository::class),
            stateMachine: $c->get(RequestStateMachine::class),
            auditService: $c->get(AuditService::class),
        ));

        // -- Controllers --

        $c->singleton(AuthController::class, fn(Container $c) => new AuthController(
            authService: $c->get(AuthService::class),
            rateLimiter: $c->get(RateLimiter::class),
            view: $c->get(View::class),
            auth: $c->get(Auth::class),
            session: $c->get(Session::class),
        ));

        $c->singleton(RequestController::class, fn(Container $c) => new RequestController(
            requestService: $c->get(RequestService::class),
            view: $c->get(View::class),
            session: $c->get(Session::class),
        ));

        $c->singleton(DispatcherController::class, fn(Container $c) => new DispatcherController(
            requestService: $c->get(RequestService::class),
            requestRepository: $c->get(RequestRepository::class),
            userRepository: $c->get(UserRepository::class),
            view: $c->get(View::class),
            auth: $c->get(Auth::class),
            session: $c->get(Session::class),
        ));

        $c->singleton(MasterController::class, fn(Container $c) => new MasterController(
            requestService: $c->get(RequestService::class),
            requestRepository: $c->get(RequestRepository::class),
            view: $c->get(View::class),
            auth: $c->get(Auth::class),
            session: $c->get(Session::class),
        ));
    }
}
