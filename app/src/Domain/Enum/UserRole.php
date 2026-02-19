<?php

declare(strict_types=1);

namespace App\Domain\Enum;

enum UserRole: string
{
    case Dispatcher = 'dispatcher';
    case Master = 'master';

    public function label(): string
    {
        return match ($this) {
            self::Dispatcher => 'Диспетчер',
            self::Master => 'Мастер',
        };
    }
}
