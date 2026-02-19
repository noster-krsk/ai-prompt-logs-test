<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use App\Domain\Enum\RequestStatus;

final class RequestRepository
{
    public function __construct(
        private readonly Database $db
    ) {}

    public function create(array $data): int
    {
        $this->db->query(
            'INSERT INTO repair_requests (client_name, phone, address, problem_text, status)
             VALUES (:client_name, :phone, :address, :problem_text, :status)',
            [
                'client_name'  => $data['client_name'],
                'phone'        => $data['phone'],
                'address'      => $data['address'],
                'problem_text' => $data['problem_text'],
                'status'       => RequestStatus::New->value,
            ]
        );

        return (int) $this->db->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->query(
            'SELECT r.*, u.name AS master_name
             FROM repair_requests r
             LEFT JOIN users u ON r.assigned_to = u.id
             WHERE r.id = :id
             LIMIT 1',
            ['id' => $id]
        );
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * SELECT FOR UPDATE — блокировка строки для предотвращения гонки.
     * Должен вызываться ТОЛЬКО внутри транзакции.
     */
    public function findByIdForUpdate(int $id): ?array
    {
        $stmt = $this->db->query(
            'SELECT * FROM repair_requests WHERE id = :id FOR UPDATE',
            ['id' => $id]
        );
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updateStatus(int $id, RequestStatus $status, ?int $assignedTo = null): void
    {
        if ($assignedTo !== null) {
            $this->db->query(
                'UPDATE repair_requests
                 SET status = :status, assigned_to = :assigned_to, updated_at = NOW()
                 WHERE id = :id',
                [
                    'status'      => $status->value,
                    'assigned_to' => $assignedTo,
                    'id'          => $id,
                ]
            );
        } else {
            $this->db->query(
                'UPDATE repair_requests SET status = :status, updated_at = NOW() WHERE id = :id',
                [
                    'status' => $status->value,
                    'id'     => $id,
                ]
            );
        }
    }

    /**
     * @return list<array>
     */
    public function findAll(?RequestStatus $statusFilter = null): array
    {
        $sql = 'SELECT r.*, u.name AS master_name
                FROM repair_requests r
                LEFT JOIN users u ON r.assigned_to = u.id';
        $params = [];

        if ($statusFilter !== null) {
            $sql .= ' WHERE r.status = :status';
            $params['status'] = $statusFilter->value;
        }

        $sql .= ' ORDER BY r.created_at DESC';

        $stmt = $this->db->query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * @return list<array>
     */
    public function findByMaster(int $masterId, ?RequestStatus $statusFilter = null): array
    {
        $sql = 'SELECT r.*, u.name AS master_name
                FROM repair_requests r
                LEFT JOIN users u ON r.assigned_to = u.id
                WHERE r.assigned_to = :master_id';
        $params = ['master_id' => $masterId];

        if ($statusFilter !== null) {
            $sql .= ' AND r.status = :status';
            $params['status'] = $statusFilter->value;
        }

        $sql .= ' ORDER BY r.created_at DESC';

        $stmt = $this->db->query($sql, $params);
        return $stmt->fetchAll();
    }
}
