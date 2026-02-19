<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use App\Domain\Enum\RequestStatus;

final class AuditLogRepository
{
    public function __construct(
        private readonly Database $db
    ) {}

    public function record(
        int $requestId,
        ?RequestStatus $oldStatus,
        RequestStatus $newStatus,
        ?int $actorId
    ): void {
        $this->db->query(
            'INSERT INTO audit_log (request_id, old_status, new_status, actor_id)
             VALUES (:request_id, :old_status, :new_status, :actor_id)',
            [
                'request_id' => $requestId,
                'old_status' => $oldStatus?->value,
                'new_status' => $newStatus->value,
                'actor_id'   => $actorId,
            ]
        );
    }

    /**
     * @return list<array>
     */
    public function findByRequest(int $requestId): array
    {
        $stmt = $this->db->query(
            'SELECT al.*, u.name AS actor_name
             FROM audit_log al
             LEFT JOIN users u ON al.actor_id = u.id
             WHERE al.request_id = :request_id
             ORDER BY al.created_at ASC',
            ['request_id' => $requestId]
        );
        return $stmt->fetchAll();
    }
}
