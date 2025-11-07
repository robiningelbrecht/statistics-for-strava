<?php

declare(strict_types=1);

namespace App\Infrastructure\Cron;

use Cron\CronExpression;

final readonly class CronAction
{
    private function __construct(
        private string $id,
        private CronExpression $expression,
        private RunnableCronAction $runnable,
    ) {
    }

    public static function create(
        string $id,
        CronExpression $expression,
        RunnableCronAction $runnable,
    ): self {
        return new self(
            id: $id,
            expression: $expression,
            runnable: $runnable,
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getExpression(): CronExpression
    {
        return $this->expression;
    }

    public function getRunnable(): RunnableCronAction
    {
        return $this->runnable;
    }
}
