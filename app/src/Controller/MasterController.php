<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Auth;
use App\Core\Router;
use App\Core\Session;
use App\Core\View;
use App\Domain\Exception\ConcurrencyException;
use App\Domain\Exception\InvalidTransitionException;
use App\Repository\RequestRepository;
use App\Service\RequestService;
use InvalidArgumentException;

final class MasterController
{
    public function __construct(
        private readonly RequestService $requestService,
        private readonly RequestRepository $requestRepository,
        private readonly View $view,
        private readonly Auth $auth,
        private readonly Session $session,
    ) {}

    public function index(): void
    {
        $requests = $this->requestRepository->findByMaster($this->auth->id());

        echo $this->view->render('master.index', [
            'requests' => $requests,
        ]);
    }

    public function takeIntoWork(): void
    {
        $requestId = (int) ($_POST['request_id'] ?? 0);

        if ($requestId === 0) {
            $this->session->flash('error', 'Укажите заявку.');
            Router::redirect('/master');
            return;
        }

        try {
            $this->requestService->takeIntoWork($requestId, $this->auth->id());
            $this->session->flash('success', 'Заявка взята в работу.');
        } catch (ConcurrencyException $e) {
            // HTTP 409 Conflict — гонка, заявка уже взята другим мастером
            http_response_code(409);
            $this->session->flash('error', $e->getMessage());
        } catch (InvalidTransitionException $e) {
            $this->session->flash('error', $e->getMessage());
        } catch (InvalidArgumentException $e) {
            $this->session->flash('error', $e->getMessage());
        }

        Router::redirect('/master');
    }

    public function finish(): void
    {
        $requestId = (int) ($_POST['request_id'] ?? 0);

        if ($requestId === 0) {
            $this->session->flash('error', 'Укажите заявку.');
            Router::redirect('/master');
            return;
        }

        try {
            $this->requestService->finish($requestId, $this->auth->id());
            $this->session->flash('success', 'Заявка завершена.');
        } catch (InvalidTransitionException $e) {
            $this->session->flash('error', $e->getMessage());
        } catch (InvalidArgumentException $e) {
            $this->session->flash('error', $e->getMessage());
        }

        Router::redirect('/master');
    }
}
