<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use RuntimeException;

final class ConcurrencyException extends RuntimeException
{
    public function __construct(string $message = 'Заявка уже взята в работу другим мастером')
    {
        parent::__construct($message);
    }
}
