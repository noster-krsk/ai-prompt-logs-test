<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Auth;
use App\Core\Router;
use App\Core\Session;
use App\Core\View;
use App\Service\AuthService;
use App\Service\RateLimiter;

final class AuthController
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly RateLimiter $rateLimiter,
        private readonly View $view,
        private readonly Auth $auth,
        private readonly Session $session,
    ) {}

    public function home(): void
    {
        if (!$this->auth->check()) {
            Router::redirect('/login');
            return;
        }

        if ($this->auth->isDispatcher()) {
            Router::redirect('/dispatcher');
        } else {
            Router::redirect('/master');
        }
    }

    public function showLogin(): void
    {
        if ($this->auth->check()) {
            $this->home();
            return;
        }

        echo $this->view->render('auth.login');
    }

    public function login(): void
    {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        // Rate limiting: макс. 5 попыток в минуту с одного IP
        if ($this->rateLimiter->tooManyAttempts("login:{$ip}", 5, 60)) {
            $this->session->flash('error', 'Слишком много попыток входа. Подождите минуту.');
            $this->session->flashOldInput(['email' => $email]);
            Router::redirect('/login');
            return;
        }

        if ($email === '' || $password === '') {
            $this->session->flash('error', 'Заполните все поля.');
            $this->session->flashOldInput(['email' => $email]);
            Router::redirect('/login');
            return;
        }

        if ($this->authService->attempt($email, $password)) {
            // Перенаправление в зависимости от роли
            if ($this->auth->isDispatcher()) {
                Router::redirect('/dispatcher');
            } else {
                Router::redirect('/master');
            }
        } else {
            $this->rateLimiter->hit("login:{$ip}", 60);
            error_log("Failed login attempt: email={$email} ip={$ip}");

            $this->session->flash('error', 'Неверный email или пароль.');
            $this->session->flashOldInput(['email' => $email]);
            Router::redirect('/login');
        }
    }

    public function logout(): void
    {
        $this->authService->logout();
        Router::redirect('/login');
    }
}
