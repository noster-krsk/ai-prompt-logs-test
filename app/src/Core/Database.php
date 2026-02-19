<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOStatement;

final class Database
{
    private readonly PDO $pdo;

    public function __construct(string $host, string $dbname, string $username, string $password, int $port = 3306)
    {
        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

        $this->pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }

    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollBack(): void
    {
        $this->pdo->rollBack();
    }

    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }
}
