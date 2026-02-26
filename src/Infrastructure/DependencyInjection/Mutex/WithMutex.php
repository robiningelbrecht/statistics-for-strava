<?php

declare(strict_types=1);

namespace App\Infrastructure\DependencyInjection\Mutex;

use App\Infrastructure\Mutex\LockName;

#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class WithMutex
{
    public function __construct(
        private LockName $lockName,
    ) {
    }

    public function getLockName(): LockName
    {
        return $this->lockName;
    }
}
