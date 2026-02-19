<?php

declare(strict_types=1);

namespace App\Core;

use App\Domain\Enum\UserRole;

final class Auth
{
    public function __construct(
        private readonly Session $session
    ) {}

    public function user(): ?array
    {
        return $this->session->get('user');
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function id(): ?int
    {
        return $this->user()['id'] ?? null;
    }

    public function role(): ?UserRole
    {
        $user = $this->user();
        if ($user === null) {
            return null;
        }
        return UserRole::from($user['role']);
    }

    public function isDispatcher(): bool
    {
        return $this->role() === UserRole::Dispatcher;
    }

    public function isMaster(): bool
    {
        return $this->role() === UserRole::Master;
    }

    public function login(array $user): void
    {
        $this->session->set('user', $user);
    }

    public function logout(): void
    {
        $this->session->destroy();
    }
}
