<?php

declare(strict_types=1);

namespace App\Infrastructure\Daemon\Mutex;

final class LockIsAlreadyAcquired extends \RuntimeException
{
    public function __construct(string $name, string $lockAcquiredBy)
    {
        parent::__construct(sprintf('Lock "%s" is already acquired by %s.', $name, $lockAcquiredBy));
    }
}
