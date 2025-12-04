<?php

declare(strict_types=1);

namespace App\Infrastructure\DependencyInjection\Mutex;

#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class WithMutex
{
    public function __construct(
        private string $lockName,
    ) {
    }

    public function getLockName(): string
    {
        return $this->lockName;
    }
}
