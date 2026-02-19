<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Database;
use App\Domain\Enum\RequestStatus;
use App\Domain\Enum\UserRole;
use App\Domain\Exception\ConcurrencyException;
use App\Domain\Exception\InvalidTransitionException;
use App\Domain\StateMachine\RequestStateMachine;
use App\Repository\RequestRepository;
use App\Repository\UserRepository;
use InvalidArgumentException;
use Throwable;

final class RequestService
{
    public function __construct(
        private readonly Database $db,
        private readonly RequestRepository $requestRepository,
        private readonly UserRepository $userRepository,
        private readonly RequestStateMachine $stateMachine,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Создание новой заявки (публичная форма).
     */
    public function create(string $clientName, string $phone, string $address, string $problemText): int
    {
        $clientName = trim($clientName);
        $phone = trim($phone);
        $address = trim($address);
        $problemText = trim($problemText);

        // Валидация обязательных полей
        $errors = [];
        if ($clientName === '') {
            $errors[] = 'Поле «Имя клиента» обязательно для заполнения.';
        }
        if ($phone === '') {
            $errors[] = 'Поле «Телефон» обязательно для заполнения.';
        }
        if ($address === '') {
            $errors[] = 'Поле «Адрес» обязательно для заполнения.';
        }
        if ($problemText === '') {
            $errors[] = 'Поле «Описание проблемы» обязательно для заполнения.';
        }

        // Валидация длины полей
        if (mb_strlen($clientName) > 255) {
            $errors[] = 'Имя клиента не должно превышать 255 символов.';
        }
        if (mb_strlen($phone) > 20) {
            $errors[] = 'Телефон не должен превышать 20 символов.';
        }
        if ($phone !== '' && !preg_match('/^[\d\s\+\-\(\)]+$/', $phone)) {
            $errors[] = 'Телефон содержит недопустимые символы.';
        }
        if (mb_strlen($address) > 500) {
            $errors[] = 'Адрес не должен превышать 500 символов.';
        }
        if (mb_strlen($problemText) > 5000) {
            $errors[] = 'Описание проблемы не должно превышать 5000 символов.';
        }

        if (!empty($errors)) {
            throw new InvalidArgumentException(implode(' ', $errors));
        }

        $requestId = $this->requestRepository->create([
            'client_name'  => $clientName,
            'phone'        => $phone,
            'address'      => $address,
            'problem_text' => $problemText,
        ]);

        // Аудит: создание заявки (null → new)
        $this->auditService->log($requestId, null, RequestStatus::New, null);

        return $requestId;
    }

    /**
     * Назначение мастера диспетчером (new → assigned).
     */
    public function assign(int $requestId, int $masterId, int $actorId): void
    {
        // Проверяем, что master_id принадлежит пользователю с ролью «мастер»
        $master = $this->userRepository->findById($masterId);
        if ($master === null || $master['role'] !== UserRole::Master->value) {
            throw new InvalidArgumentException('Выбранный мастер не найден.');
        }

        $this->db->beginTransaction();
        try {
            $request = $this->requestRepository->findByIdForUpdate($requestId);

            if ($request === null) {
                throw new InvalidArgumentException('Заявка не найдена.');
            }

            $currentStatus = RequestStatus::from($request['status']);
            $newStatus = RequestStatus::Assigned;

            // Валидация перехода через State Machine
            $this->stateMachine->assertTransition($currentStatus, $newStatus);

            // Обновление статуса и назначение мастера
            $this->requestRepository->updateStatus($requestId, $newStatus, $masterId);

            // Аудит
            $this->auditService->log($requestId, $currentStatus, $newStatus, $actorId);

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Мастер берёт заявку в работу (assigned → in_progress).
     *
     * КРИТИЧЕСКАЯ СЕКЦИЯ: защита от гонки через SELECT FOR UPDATE.
     * Если два мастера одновременно пытаются взять заявку,
     * только один успеет — второй получит ConcurrencyException.
     */
    public function takeIntoWork(int $requestId, int $masterId): void
    {
        $this->db->beginTransaction();
        try {
            // SELECT FOR UPDATE — блокировка строки
            $request = $this->requestRepository->findByIdForUpdate($requestId);

            if ($request === null) {
                throw new InvalidArgumentException('Заявка не найдена.');
            }

            $currentStatus = RequestStatus::from($request['status']);

            // Если заявка уже в работе — это гонка, второй мастер опоздал
            if ($currentStatus === RequestStatus::InProgress) {
                throw new ConcurrencyException();
            }

            // Если заявка уже завершена или отменена
            if ($currentStatus === RequestStatus::Done || $currentStatus === RequestStatus::Canceled) {
                throw InvalidTransitionException::create($currentStatus, RequestStatus::InProgress);
            }

            // Проверяем, что заявка назначена именно этому мастеру
            if ((int) $request['assigned_to'] !== $masterId) {
                throw new InvalidArgumentException('Заявка назначена другому мастеру.');
            }

            $newStatus = RequestStatus::InProgress;

            // Валидация перехода через State Machine
            $this->stateMachine->assertTransition($currentStatus, $newStatus);

            // Обновление статуса
            $this->requestRepository->updateStatus($requestId, $newStatus);

            // Аудит
            $this->auditService->log($requestId, $currentStatus, $newStatus, $masterId);

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Мастер завершает заявку (in_progress → done).
     */
    public function finish(int $requestId, int $masterId): void
    {
        $this->db->beginTransaction();
        try {
            $request = $this->requestRepository->findByIdForUpdate($requestId);

            if ($request === null) {
                throw new InvalidArgumentException('Заявка не найдена.');
            }

            // Проверяем, что заявка назначена этому мастеру
            if ((int) $request['assigned_to'] !== $masterId) {
                throw new InvalidArgumentException('Заявка назначена другому мастеру.');
            }

            $currentStatus = RequestStatus::from($request['status']);
            $newStatus = RequestStatus::Done;

            // Валидация перехода через State Machine
            $this->stateMachine->assertTransition($currentStatus, $newStatus);

            // Обновление статуса
            $this->requestRepository->updateStatus($requestId, $newStatus);

            // Аудит
            $this->auditService->log($requestId, $currentStatus, $newStatus, $masterId);

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Отмена заявки диспетчером (new|assigned → canceled).
     */
    public function cancel(int $requestId, int $actorId): void
    {
        $this->db->beginTransaction();
        try {
            $request = $this->requestRepository->findByIdForUpdate($requestId);

            if ($request === null) {
                throw new InvalidArgumentException('Заявка не найдена.');
            }

            $currentStatus = RequestStatus::from($request['status']);
            $newStatus = RequestStatus::Canceled;

            // Валидация перехода через State Machine
            $this->stateMachine->assertTransition($currentStatus, $newStatus);

            // Обновление статуса
            $this->requestRepository->updateStatus($requestId, $newStatus);

            // Аудит
            $this->auditService->log($requestId, $currentStatus, $newStatus, $actorId);

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
