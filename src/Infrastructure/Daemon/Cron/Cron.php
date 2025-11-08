<?php

declare(strict_types=1);

namespace App\Infrastructure\Daemon\Cron;

interface Cron extends \IteratorAggregate
{
    public function getRunnable(string $id): RunnableCronAction;
}
