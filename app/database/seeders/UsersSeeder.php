<?php

declare(strict_types=1);

namespace App\Database\Seeders;

use App\Core\Database;

final class UsersSeeder
{
    public function run(Database $db): void
    {
        $passwordHash = password_hash('password', PASSWORD_BCRYPT);

        $users = [
            ['Диспетчер Иванова', 'dispatcher@example.com', 'dispatcher'],
            ['Мастер Петров', 'master1@example.com', 'master'],
            ['Мастер Сидоров', 'master2@example.com', 'master'],
        ];

        foreach ($users as [$name, $email, $role]) {
            $db->query(
                'INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)',
                [
                    'name'     => $name,
                    'email'    => $email,
                    'password' => $passwordHash,
                    'role'     => $role,
                ]
            );
        }
    }
}
