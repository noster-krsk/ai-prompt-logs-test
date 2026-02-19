<?php

declare(strict_types=1);

namespace App\Domain\Enum;

enum RequestStatus: string
{
    case New = 'new';
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case Done = 'done';
    case Canceled = 'canceled';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Новая',
            self::Assigned => 'Назначена',
            self::InProgress => 'В работе',
            self::Done => 'Выполнена',
            self::Canceled => 'Отменена',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::New => 'badge-new',
            self::Assigned => 'badge-assigned',
            self::InProgress => 'badge-in-progress',
            self::Done => 'badge-done',
            self::Canceled => 'badge-canceled',
        };
    }
}
