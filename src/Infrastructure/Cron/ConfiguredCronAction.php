<?php

declare(strict_types=1);

namespace App\Infrastructure\Cron;

use Cron\CronExpression;

final readonly class ConfiguredCronAction
{
    private function __construct(
        private string $cronActionId,
        private CronExpression $cronExpression,
    ) {
    }

    public static function create(
        string $cronActionId,
        CronExpression $cronExpression,
    ): self {
        return new self(
            cronActionId: $cronActionId,
            cronExpression: $cronExpression,
        );
    }

    public function getId(): string
    {
        return $this->cronActionId;
    }

    public function getCronExpression(): CronExpression
    {
        return $this->cronExpression;
    }
}
