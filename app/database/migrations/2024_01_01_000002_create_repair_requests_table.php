<?php

declare(strict_types=1);

use App\Core\Database;

return new class {
    public function up(Database $db): void
    {
        $db->query("
            CREATE TABLE IF NOT EXISTS repair_requests (
                id INT PRIMARY KEY AUTO_INCREMENT,
                client_name VARCHAR(255) NOT NULL,
                phone VARCHAR(50) NOT NULL,
                address TEXT NOT NULL,
                problem_text TEXT NOT NULL,
                status ENUM('new', 'assigned', 'in_progress', 'done', 'canceled') NOT NULL DEFAULT 'new',
                assigned_to INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_status (status),
                INDEX idx_assigned_to (assigned_to),
                CONSTRAINT fk_assigned_to FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(Database $db): void
    {
        $db->query("DROP TABLE IF EXISTS repair_requests");
    }
};
