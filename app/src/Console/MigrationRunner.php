<?php

declare(strict_types=1);

namespace App\Console;

use App\Core\Database;

final class MigrationRunner
{
    private string $migrationsPath;

    public function __construct(
        private readonly Database $db
    ) {
        $this->migrationsPath = dirname(__DIR__, 2) . '/database/migrations';
    }

    /**
     * Выполнить все непримёненные миграции.
     */
    public function runPending(): void
    {
        $this->ensureMigrationsTable();

        $executed = $this->getExecutedMigrations();
        $files = $this->getMigrationFiles();
        $pending = array_diff($files, $executed);

        if (empty($pending)) {
            echo "  Нет новых миграций.\n";
            return;
        }

        $batch = $this->getNextBatch();

        foreach ($pending as $file) {
            $this->runMigration($file, 'up', $batch);
        }
    }

    /**
     * Удалить все таблицы и запустить миграции заново.
     */
    public function fresh(): void
    {
        echo "  Удаление всех таблиц...\n";
        $this->dropAllTables();
        echo "  Запуск миграций...\n";
        $this->runPending();
    }

    /**
     * Показать статус миграций.
     */
    public function status(): void
    {
        $this->ensureMigrationsTable();

        $executed = $this->getExecutedMigrations();
        $files = $this->getMigrationFiles();

        if (empty($files)) {
            echo "  Миграции не найдены.\n";
            return;
        }

        echo str_pad('Миграция', 60) . "Статус\n";
        echo str_repeat('-', 75) . "\n";

        foreach ($files as $file) {
            $status = in_array($file, $executed, true) ? "\033[32mВыполнена\033[0m" : "\033[33mОжидает\033[0m";
            echo str_pad($file, 60) . $status . "\n";
        }
    }

    private function runMigration(string $file, string $direction, int $batch): void
    {
        $migration = require $this->migrationsPath . '/' . $file;

        try {
            // DDL-операции (CREATE/DROP TABLE) в MySQL выполняют автокоммит,
            // поэтому транзакции здесь не используются.
            $migration->$direction($this->db);

            if ($direction === 'up') {
                $this->db->query(
                    'INSERT INTO migrations (migration, batch) VALUES (:migration, :batch)',
                    ['migration' => $file, 'batch' => $batch]
                );
            }

            echo "  \033[32m✓\033[0m {$file}\n";
        } catch (\Throwable $e) {
            echo "  \033[31m✗\033[0m {$file}: {$e->getMessage()}\n";
            throw $e;
        }
    }

    private function ensureMigrationsTable(): void
    {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS migrations (
                id INT PRIMARY KEY AUTO_INCREMENT,
                migration VARCHAR(255) NOT NULL,
                batch INT NOT NULL,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    private function dropAllTables(): void
    {
        $this->db->query('SET FOREIGN_KEY_CHECKS = 0');

        $stmt = $this->db->query('SHOW TABLES');
        $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            $this->db->query("DROP TABLE IF EXISTS `{$table}`");
            echo "    Удалена: {$table}\n";
        }

        $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
    }

    /**
     * @return list<string>
     */
    private function getMigrationFiles(): array
    {
        if (!is_dir($this->migrationsPath)) {
            return [];
        }

        $files = glob($this->migrationsPath . '/*.php');
        $files = array_map('basename', $files);
        sort($files);

        return $files;
    }

    /**
     * @return list<string>
     */
    private function getExecutedMigrations(): array
    {
        $stmt = $this->db->query('SELECT migration FROM migrations ORDER BY id');
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    private function getNextBatch(): int
    {
        $stmt = $this->db->query('SELECT COALESCE(MAX(batch), 0) + 1 FROM migrations');
        return (int) $stmt->fetchColumn();
    }
}
