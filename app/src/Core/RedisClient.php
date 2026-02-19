<?php

declare(strict_types=1);

namespace App\Core;

use Redis;

final class RedisClient
{
    private readonly Redis $redis;

    public function __construct(string $host = '127.0.0.1', int $port = 6379, string $password = '')
    {
        $this->redis = new Redis();
        $this->redis->connect($host, $port);

        if ($password !== '') {
            $this->redis->auth($password);
        }
    }

    public function get(string $key): mixed
    {
        return $this->redis->get($key);
    }

    public function set(string $key, mixed $value, int $ttl = 0): bool
    {
        if ($ttl > 0) {
            return $this->redis->setex($key, $ttl, $value);
        }
        return $this->redis->set($key, $value);
    }

    public function del(string $key): int
    {
        return $this->redis->del($key);
    }

    public function exists(string $key): bool
    {
        return (bool) $this->redis->exists($key);
    }

    public function getRedis(): Redis
    {
        return $this->redis;
    }
}
