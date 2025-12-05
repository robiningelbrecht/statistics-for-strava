<?php

declare(strict_types=1);

namespace App\Application\Build\BuildMonthlyStatsHtml;

use App\Infrastructure\CQRS\Command\DomainCommand;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class BuildMonthlyStatsHtml extends DomainCommand
{
    public function __construct(
        private SerializableDateTime $now,
    ) {
    }

    public function getCurrentDateTime(): SerializableDateTime
    {
        return $this->now;
    }
}
