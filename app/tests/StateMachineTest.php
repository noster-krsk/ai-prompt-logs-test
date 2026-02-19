<?php

declare(strict_types=1);

namespace Tests;

use App\Domain\Enum\RequestStatus;
use App\Domain\Exception\InvalidTransitionException;
use App\Domain\StateMachine\RequestStateMachine;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class StateMachineTest extends TestCase
{
    private RequestStateMachine $stateMachine;

    protected function setUp(): void
    {
        $this->stateMachine = new RequestStateMachine();
    }

    /**
     * Допустимые переходы статусов.
     */
    public static function validTransitionsProvider(): array
    {
        return [
            'new → assigned'        => [RequestStatus::New, RequestStatus::Assigned],
            'new → canceled'        => [RequestStatus::New, RequestStatus::Canceled],
            'assigned → in_progress' => [RequestStatus::Assigned, RequestStatus::InProgress],
            'assigned → canceled'   => [RequestStatus::Assigned, RequestStatus::Canceled],
            'in_progress → done'    => [RequestStatus::InProgress, RequestStatus::Done],
        ];
    }

    /**
     * Недопустимые переходы статусов.
     */
    public static function invalidTransitionsProvider(): array
    {
        return [
            'new → in_progress'     => [RequestStatus::New, RequestStatus::InProgress],
            'new → done'            => [RequestStatus::New, RequestStatus::Done],
            'assigned → done'       => [RequestStatus::Assigned, RequestStatus::Done],
            'assigned → new'        => [RequestStatus::Assigned, RequestStatus::New],
            'in_progress → new'     => [RequestStatus::InProgress, RequestStatus::New],
            'in_progress → assigned' => [RequestStatus::InProgress, RequestStatus::Assigned],
            'in_progress → canceled' => [RequestStatus::InProgress, RequestStatus::Canceled],
            'done → new'            => [RequestStatus::Done, RequestStatus::New],
            'done → assigned'       => [RequestStatus::Done, RequestStatus::Assigned],
            'done → in_progress'    => [RequestStatus::Done, RequestStatus::InProgress],
            'done → canceled'       => [RequestStatus::Done, RequestStatus::Canceled],
            'canceled → new'        => [RequestStatus::Canceled, RequestStatus::New],
            'canceled → assigned'   => [RequestStatus::Canceled, RequestStatus::Assigned],
            'canceled → in_progress' => [RequestStatus::Canceled, RequestStatus::InProgress],
            'canceled → done'       => [RequestStatus::Canceled, RequestStatus::Canceled],
        ];
    }

    #[Test]
    #[DataProvider('validTransitionsProvider')]
    public function допустимый_переход_разрешён(RequestStatus $from, RequestStatus $to): void
    {
        $this->assertTrue(
            $this->stateMachine->canTransition($from, $to),
            "Переход {$from->value} → {$to->value} должен быть разрешён"
        );

        // Не должен выбрасывать исключение
        $this->stateMachine->assertTransition($from, $to);
        $this->addToAssertionCount(1);
    }

    #[Test]
    #[DataProvider('invalidTransitionsProvider')]
    public function недопустимый_переход_запрещён(RequestStatus $from, RequestStatus $to): void
    {
        $this->assertFalse(
            $this->stateMachine->canTransition($from, $to),
            "Переход {$from->value} → {$to->value} должен быть запрещён"
        );
    }

    #[Test]
    #[DataProvider('invalidTransitionsProvider')]
    public function недопустимый_переход_выбрасывает_исключение(RequestStatus $from, RequestStatus $to): void
    {
        $this->expectException(InvalidTransitionException::class);
        $this->expectExceptionMessageMatches('/Невозможно перевести заявку/u');

        $this->stateMachine->assertTransition($from, $to);
    }

    #[Test]
    public function исключение_содержит_названия_статусов_на_русском(): void
    {
        try {
            $this->stateMachine->assertTransition(RequestStatus::Done, RequestStatus::New);
            $this->fail('Должно было быть выброшено исключение InvalidTransitionException');
        } catch (InvalidTransitionException $e) {
            $this->assertStringContainsString('Выполнена', $e->getMessage());
            $this->assertStringContainsString('Новая', $e->getMessage());
        }
    }

    #[Test]
    public function список_допустимых_переходов_для_статуса(): void
    {
        $transitions = $this->stateMachine->allowedTransitions(RequestStatus::New);
        $values = array_map(fn(RequestStatus $s) => $s->value, $transitions);

        $this->assertContains('assigned', $values);
        $this->assertContains('canceled', $values);
        $this->assertCount(2, $transitions);
    }

    #[Test]
    public function нет_переходов_из_конечных_статусов(): void
    {
        $this->assertEmpty($this->stateMachine->allowedTransitions(RequestStatus::Done));
        $this->assertEmpty($this->stateMachine->allowedTransitions(RequestStatus::Canceled));
    }
}
