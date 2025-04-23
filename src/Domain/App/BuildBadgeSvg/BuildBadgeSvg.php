<?php

declare(strict_types=1);

namespace App\Domain\App\BuildBadgeSvg;

use App\Infrastructure\CQRS\Command\DomainCommand;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class BuildBadgeSvg extends DomainCommand
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
