<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\Enum\RequestStatus;
use App\Repository\AuditLogRepository;

final class AuditService
{
    public function __construct(
        private readonly AuditLogRepository $auditLogRepository,
    ) {}

    public function log(
        int $requestId,
        ?RequestStatus $oldStatus,
        RequestStatus $newStatus,
        ?int $actorId
    ): void {
        $this->auditLogRepository->record($requestId, $oldStatus, $newStatus, $actorId);
    }
}
