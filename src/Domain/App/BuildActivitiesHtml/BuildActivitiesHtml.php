<?php

declare(strict_types=1);

namespace App\Domain\App\BuildActivitiesHtml;

use App\Infrastructure\CQRS\Command\DomainCommand;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class BuildActivitiesHtml extends DomainCommand
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
