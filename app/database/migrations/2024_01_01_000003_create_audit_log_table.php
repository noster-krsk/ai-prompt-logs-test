<?php

declare(strict_types=1);

use App\Core\Database;

return new class {
    public function up(Database $db): void
    {
        $db->query("
            CREATE TABLE IF NOT EXISTS audit_log (
                id BIGINT PRIMARY KEY AUTO_INCREMENT,
                request_id INT NOT NULL,
                old_status VARCHAR(20) NULL,
                new_status VARCHAR(20) NOT NULL,
                actor_id INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_request_id (request_id),
                CONSTRAINT fk_audit_request FOREIGN KEY (request_id) REFERENCES repair_requests(id) ON DELETE CASCADE,
                CONSTRAINT fk_audit_actor FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(Database $db): void
    {
        $db->query("DROP TABLE IF EXISTS audit_log");
    }
};
