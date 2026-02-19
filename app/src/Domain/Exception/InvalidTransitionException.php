<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use App\Domain\Enum\RequestStatus;
use DomainException;

final class InvalidTransitionException extends DomainException
{
    public static function create(RequestStatus $from, RequestStatus $to): self
    {
        return new self(
            "Невозможно перевести заявку из статуса '{$from->label()}' в статус '{$to->label()}'"
        );
    }
}
