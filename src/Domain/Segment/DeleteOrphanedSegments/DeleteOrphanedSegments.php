<?php

declare(strict_types=1);

namespace App\Domain\Segment\DeleteOrphanedSegments;

use App\Infrastructure\CQRS\Command\DomainCommand;

final readonly class DeleteOrphanedSegments extends DomainCommand
{
}
