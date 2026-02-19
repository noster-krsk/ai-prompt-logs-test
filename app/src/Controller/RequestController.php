<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Router;
use App\Core\Session;
use App\Core\View;
use App\Service\RequestService;
use InvalidArgumentException;

final class RequestController
{
    public function __construct(
        private readonly RequestService $requestService,
        private readonly View $view,
        private readonly Session $session,
    ) {}

    public function showCreate(): void
    {
        echo $this->view->render('requests.create');
    }

    public function store(): void
    {
        $clientName  = $_POST['client_name'] ?? '';
        $phone       = $_POST['phone'] ?? '';
        $address     = $_POST['address'] ?? '';
        $problemText = $_POST['problem_text'] ?? '';

        try {
            $this->requestService->create($clientName, $phone, $address, $problemText);
            $this->session->flash('success', 'Заявка успешно создана.');
        } catch (InvalidArgumentException $e) {
            $this->session->flash('error', $e->getMessage());
            $this->session->flashOldInput([
                'client_name'  => $clientName,
                'phone'        => $phone,
                'address'      => $address,
                'problem_text' => $problemText,
            ]);
        }

        Router::redirect('/requests/create');
    }
}
