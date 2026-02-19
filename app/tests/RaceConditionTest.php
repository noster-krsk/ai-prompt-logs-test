<?php

declare(strict_types=1);

namespace Tests;

use App\Core\Database;
use App\Domain\Enum\RequestStatus;
use App\Domain\Exception\ConcurrencyException;
use App\Domain\Exception\InvalidTransitionException;
use App\Domain\StateMachine\RequestStateMachine;
use App\Repository\AuditLogRepository;
use App\Repository\RequestRepository;
use App\Repository\UserRepository;
use App\Service\AuditService;
use App\Service\RequestService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Интеграционный тест: проверка защиты от гонки при взятии заявки в работу.
 *
 * Требует работающей MySQL (запускать внутри Docker-контейнера).
 */
final class RaceConditionTest extends TestCase
{
    private Database $db;
    private RequestService $requestService;
    private RequestRepository $requestRepository;
    private int $testRequestId;
    private int $masterId = 2; // Мастер Петров (из seeders)

    protected function setUp(): void
    {
        $host = $_ENV['MYSQL_HOST'] ?? getenv('MYSQL_HOST') ?: 'mysql';
        $dbname = $_ENV['MYSQL_DATABASE'] ?? getenv('MYSQL_DATABASE') ?: 'app_database';
        $user = $_ENV['MYSQL_USER'] ?? getenv('MYSQL_USER') ?: 'app_user';
        $password = $_ENV['MYSQL_PASSWORD'] ?? getenv('MYSQL_PASSWORD') ?: 'app_password';

        try {
            $this->db = new Database($host, $dbname, $user, $password);
        } catch (\Exception $e) {
            $this->markTestSkipped('MySQL недоступна: ' . $e->getMessage());
        }

        $this->requestRepository = new RequestRepository($this->db);
        $userRepository = new UserRepository($this->db);
        $auditLogRepository = new AuditLogRepository($this->db);
        $stateMachine = new RequestStateMachine();
        $auditService = new AuditService($auditLogRepository);

        $this->requestService = new RequestService(
            $this->db,
            $this->requestRepository,
            $userRepository,
            $stateMachine,
            $auditService,
        );

        // Создаём тестовую заявку в статусе assigned
        $this->db->query(
            "INSERT INTO repair_requests (client_name, phone, address, problem_text, status, assigned_to)
             VALUES ('Тест Гонки', '+70000000000', 'ул. Тестовая 1', 'Проверка race condition', 'assigned', :master_id)",
            ['master_id' => $this->masterId]
        );
        $this->testRequestId = (int) $this->db->lastInsertId();
    }

    protected function tearDown(): void
    {
        if (isset($this->db) && isset($this->testRequestId)) {
            // Удаляем тестовые данные
            $this->db->query('DELETE FROM audit_log WHERE request_id = :id', ['id' => $this->testRequestId]);
            $this->db->query('DELETE FROM repair_requests WHERE id = :id', ['id' => $this->testRequestId]);
        }
    }

    #[Test]
    public function первый_мастер_успешно_берёт_заявку_в_работу(): void
    {
        $this->requestService->takeIntoWork($this->testRequestId, $this->masterId);

        $request = $this->requestRepository->findById($this->testRequestId);
        $this->assertSame('in_progress', $request['status']);
    }

    #[Test]
    public function повторное_взятие_в_работу_выбрасывает_исключение(): void
    {
        // Первый вызов — успешно
        $this->requestService->takeIntoWork($this->testRequestId, $this->masterId);

        // Второй вызов — должен выбросить исключение (гонка)
        $this->expectException(ConcurrencyException::class);
        $this->expectExceptionMessage('Заявка уже взята в работу другим мастером');

        $this->requestService->takeIntoWork($this->testRequestId, $this->masterId);
    }

    #[Test]
    public function аудит_содержит_только_один_переход_assigned_to_in_progress(): void
    {
        $this->requestService->takeIntoWork($this->testRequestId, $this->masterId);

        // Попытка повторного взятия — ловим исключение
        try {
            $this->requestService->takeIntoWork($this->testRequestId, $this->masterId);
        } catch (ConcurrencyException) {
            // Ожидаемо
        }

        // Проверяем аудит-лог
        $auditLogRepo = new AuditLogRepository($this->db);
        $logs = $auditLogRepo->findByRequest($this->testRequestId);

        $transitionLogs = array_filter($logs, fn(array $log) =>
            $log['old_status'] === 'assigned' && $log['new_status'] === 'in_progress'
        );

        $this->assertCount(1, $transitionLogs, 'Должна быть ровно одна запись перехода assigned → in_progress');
    }

    #[Test]
    public function параллельное_взятие_с_двумя_соединениями(): void
    {
        $host = $_ENV['MYSQL_HOST'] ?? getenv('MYSQL_HOST') ?: 'mysql';
        $dbname = $_ENV['MYSQL_DATABASE'] ?? getenv('MYSQL_DATABASE') ?: 'app_database';
        $user = $_ENV['MYSQL_USER'] ?? getenv('MYSQL_USER') ?: 'app_user';
        $password = $_ENV['MYSQL_PASSWORD'] ?? getenv('MYSQL_PASSWORD') ?: 'app_password';

        // Создаём два отдельных соединения (имитация двух мастеров)
        $db1 = new Database($host, $dbname, $user, $password);
        $db2 = new Database($host, $dbname, $user, $password);

        // Соединение 1: начинаем транзакцию и блокируем строку
        $db1->beginTransaction();
        $stmt1 = $db1->query(
            'SELECT * FROM repair_requests WHERE id = :id FOR UPDATE',
            ['id' => $this->testRequestId]
        );
        $row1 = $stmt1->fetch();
        $this->assertSame('assigned', $row1['status']);

        // Обновляем статус через первое соединение
        $db1->query(
            'UPDATE repair_requests SET status = :status WHERE id = :id',
            ['status' => 'in_progress', 'id' => $this->testRequestId]
        );
        $db1->commit();

        // Соединение 2: теперь читает обновлённую строку
        $db2->beginTransaction();
        $stmt2 = $db2->query(
            'SELECT * FROM repair_requests WHERE id = :id FOR UPDATE',
            ['id' => $this->testRequestId]
        );
        $row2 = $stmt2->fetch();
        $db2->commit();

        // Второе соединение видит уже обновлённый статус
        $this->assertSame('in_progress', $row2['status'],
            'Второе соединение должно увидеть статус in_progress после коммита первого');
    }
}
