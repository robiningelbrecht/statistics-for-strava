<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Cron;

use App\Infrastructure\Cron\Cron;
use App\Infrastructure\Cron\RunnableCronAction;

class FakeCron implements Cron
{
    /** @var RunnableCronAction[] */
    private array $runnableCronActions;

    public function __construct()
    {
        $this->runnableCronActions = [
            'fake' => new FakeRunnableCronAction(),
        ];
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->runnableCronActions);
    }

    public function getRunnable(string $id): RunnableCronAction
    {
        return $this->runnableCronActions[$id];
    }
}
