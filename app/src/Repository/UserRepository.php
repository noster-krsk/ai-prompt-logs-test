<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use App\Domain\Enum\UserRole;

final class UserRepository
{
    public function __construct(
        private readonly Database $db
    ) {}

    /**
     * Поиск по email — возвращает ВСЕ поля (включая password) для аутентификации.
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->query(
            'SELECT * FROM users WHERE email = :email LIMIT 1',
            ['email' => $email]
        );
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->query(
            'SELECT id, name, email, role FROM users WHERE id = :id LIMIT 1',
            ['id' => $id]
        );
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * @return list<array>
     */
    public function findByRole(UserRole $role): array
    {
        $stmt = $this->db->query(
            'SELECT id, name, email, role FROM users WHERE role = :role ORDER BY name ASC',
            ['role' => $role->value]
        );
        return $stmt->fetchAll();
    }
}
