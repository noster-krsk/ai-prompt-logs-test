<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Auth;
use App\Repository\UserRepository;

final class AuthService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly Auth $auth,
    ) {}

    /**
     * Попытка входа по email и паролю.
     * Возвращает true при успехе.
     */
    public function attempt(string $email, string $password): bool
    {
        $user = $this->userRepository->findByEmail($email);

        if ($user === null) {
            return false;
        }

        if (!password_verify($password, $user['password'])) {
            return false;
        }

        // Защита от Session Fixation: новый session ID после входа
        session_regenerate_id(true);

        // Не храним пароль в сессии
        unset($user['password']);
        $this->auth->login($user);

        return true;
    }

    public function logout(): void
    {
        $this->auth->logout();
    }
}
