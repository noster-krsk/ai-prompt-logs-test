<?php

declare(strict_types=1);

namespace App\Domain\StateMachine;

use App\Domain\Enum\RequestStatus;
use App\Domain\Exception\InvalidTransitionException;

final class RequestStateMachine
{
    /**
     * Карта разрешённых переходов статусов.
     *
     * @var array<string, list<string>>
     */
    private const array TRANSITIONS = [
        'new'         => ['assigned', 'canceled'],
        'assigned'    => ['in_progress', 'canceled'],
        'in_progress' => ['done'],
    ];

    public function canTransition(RequestStatus $from, RequestStatus $to): bool
    {
        $allowed = self::TRANSITIONS[$from->value] ?? [];
        return in_array($to->value, $allowed, true);
    }

    public function assertTransition(RequestStatus $from, RequestStatus $to): void
    {
        if (!$this->canTransition($from, $to)) {
            throw InvalidTransitionException::create($from, $to);
        }
    }

    /**
     * Возвращает допустимые целевые статусы для данного статуса.
     *
     * @return list<RequestStatus>
     */
    public function allowedTransitions(RequestStatus $from): array
    {
        $allowed = self::TRANSITIONS[$from->value] ?? [];
        return array_map(fn(string $s) => RequestStatus::from($s), $allowed);
    }
}
