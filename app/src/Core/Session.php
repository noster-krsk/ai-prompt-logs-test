<?php

declare(strict_types=1);

namespace App\Core;

final class Session
{
    public function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start([
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax',
                'cookie_path' => '/',
                'use_strict_mode' => true,
            ]);
        }

        // Переносим flash-сообщения из "new" в "old" и очищаем "old" от предыдущего запроса
        $this->ageFlashData();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function destroy(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }

    /**
     * Устанавливает flash-сообщение (доступно только в следующем запросе).
     */
    public function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash_new'][$key] = $value;
    }

    /**
     * Получает flash-сообщение.
     */
    public function getFlash(string $key, mixed $default = null): mixed
    {
        return $_SESSION['_flash_old'][$key] ?? $default;
    }

    /**
     * Возвращает все flash-сообщения.
     */
    public function getAllFlash(): array
    {
        return $_SESSION['_flash_old'] ?? [];
    }

    /**
     * Сохраняет старые данные ввода для повторного заполнения формы.
     */
    public function flashOldInput(array $data): void
    {
        $_SESSION['_flash_new']['_old_input'] = $data;
    }

    /**
     * Получает старое значение поля формы.
     */
    public function oldInput(string $key, string $default = ''): string
    {
        return $_SESSION['_flash_old']['_old_input'][$key] ?? $default;
    }

    public function getCsrfToken(): string
    {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf_token'];
    }

    public function validateCsrfToken(string $token): bool
    {
        $stored = $_SESSION['_csrf_token'] ?? '';
        return $stored !== '' && hash_equals($stored, $token);
    }

    /**
     * Перемещает flash-данные: new → old, old удаляется.
     */
    private function ageFlashData(): void
    {
        $_SESSION['_flash_old'] = $_SESSION['_flash_new'] ?? [];
        $_SESSION['_flash_new'] = [];
    }
}
