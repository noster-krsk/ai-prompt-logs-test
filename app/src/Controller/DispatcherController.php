<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Auth;
use App\Core\Router;
use App\Core\Session;
use App\Core\View;
use App\Domain\Enum\RequestStatus;
use App\Domain\Enum\UserRole;
use App\Domain\Exception\InvalidTransitionException;
use App\Repository\RequestRepository;
use App\Repository\UserRepository;
use App\Service\RequestService;
use InvalidArgumentException;

final class DispatcherController
{
    public function __construct(
        private readonly RequestService $requestService,
        private readonly RequestRepository $requestRepository,
        private readonly UserRepository $userRepository,
        private readonly View $view,
        private readonly Auth $auth,
        private readonly Session $session,
    ) {}

    public function index(): void
    {
        $statusFilter = null;
        $filterValue = $_GET['status'] ?? '';

        if ($filterValue !== '') {
            $statusFilter = RequestStatus::tryFrom($filterValue);
        }

        $requests = $this->requestRepository->findAll($statusFilter);
        $masters = $this->userRepository->findByRole(UserRole::Master);
        $statuses = RequestStatus::cases();

        echo $this->view->render('dispatcher.index', [
            'requests'     => $requests,
            'masters'      => $masters,
            'statuses'     => $statuses,
            'currentFilter' => $filterValue,
        ]);
    }

    public function assign(): void
    {
        $requestId = (int) ($_POST['request_id'] ?? 0);
        $masterId  = (int) ($_POST['master_id'] ?? 0);

        if ($requestId === 0 || $masterId === 0) {
            $this->session->flash('error', 'Укажите заявку и мастера.');
            Router::redirect('/dispatcher');
            return;
        }

        try {
            $this->requestService->assign($requestId, $masterId, $this->auth->id());
            $this->session->flash('success', 'Мастер назначен.');
        } catch (InvalidTransitionException $e) {
            $this->session->flash('error', $e->getMessage());
        } catch (InvalidArgumentException $e) {
            $this->session->flash('error', $e->getMessage());
        }

        Router::redirect('/dispatcher');
    }

    public function cancel(): void
    {
        $requestId = (int) ($_POST['request_id'] ?? 0);

        if ($requestId === 0) {
            $this->session->flash('error', 'Укажите заявку.');
            Router::redirect('/dispatcher');
            return;
        }

        try {
            $this->requestService->cancel($requestId, $this->auth->id());
            $this->session->flash('success', 'Заявка отменена.');
        } catch (InvalidTransitionException $e) {
            $this->session->flash('error', $e->getMessage());
        } catch (InvalidArgumentException $e) {
            $this->session->flash('error', $e->getMessage());
        }

        Router::redirect('/dispatcher');
    }
}
