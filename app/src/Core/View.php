<?php

declare(strict_types=1);

namespace App\Core;

use eftec\bladeone\BladeOne;

final class View
{
    private readonly BladeOne $blade;

    public function __construct(
        private readonly Auth $auth,
        private readonly Session $session,
        string $viewsPath,
        string $cachePath
    ) {
        $mode = ($_ENV['APP_DEBUG'] ?? 'false') === 'true'
            ? BladeOne::MODE_DEBUG
            : BladeOne::MODE_AUTO;

        $this->blade = new BladeOne($viewsPath, $cachePath, $mode);
    }

    public function render(string $template, array $data = []): string
    {
        // Глобальные переменные для всех шаблонов
        $data['currentUser'] = $this->auth->user();
        $data['auth'] = $this->auth;
        $data['flash'] = $this->session->getAllFlash();
        $data['session'] = $this->session;
        $data['csrfToken'] = $this->session->getCsrfToken();

        return $this->blade->run($template, $data);
    }
}
