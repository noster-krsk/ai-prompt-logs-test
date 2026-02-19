<?php

declare(strict_types=1);

namespace App\Core;

use Closure;
use RuntimeException;

final class Container
{
    /** @var array<string, Closure> */
    private array $bindings = [];

    /** @var array<string, object> */
    private array $instances = [];

    /** @var array<string, bool> */
    private array $singletons = [];

    public function bind(string $abstract, Closure $factory): void
    {
        $this->bindings[$abstract] = $factory;
    }

    public function singleton(string $abstract, Closure $factory): void
    {
        $this->bindings[$abstract] = $factory;
        $this->singletons[$abstract] = true;
    }

    public function get(string $abstract): object
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        if (!isset($this->bindings[$abstract])) {
            throw new RuntimeException("Сервис '{$abstract}' не зарегистрирован в контейнере.");
        }

        $instance = ($this->bindings[$abstract])($this);

        if (isset($this->singletons[$abstract])) {
            $this->instances[$abstract] = $instance;
        }

        return $instance;
    }

    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }
}
