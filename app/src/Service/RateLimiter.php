<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\RedisClient;

final class RateLimiter
{
    public function __construct(
        private readonly RedisClient $redis,
    ) {}

    public function tooManyAttempts(string $key, int $maxAttempts, int $decaySeconds): bool
    {
        $attempts = (int) $this->redis->get("rate_limit:{$key}");
        return $attempts >= $maxAttempts;
    }

    public function hit(string $key, int $decaySeconds): void
    {
        $redisKey = "rate_limit:{$key}";
        $redis = $this->redis->getRedis();

        $current = $redis->incr($redisKey);
        if ($current === 1) {
            $redis->expire($redisKey, $decaySeconds);
        }
    }
}
