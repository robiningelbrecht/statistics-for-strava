<?php

declare(strict_types=1);

namespace App\Domain\Milestone;

use Ramsey\Uuid\Uuid;

final readonly class RandomMilestoneIdFactory implements MilestoneIdFactory
{
    public function create(): MilestoneId
    {
        return MilestoneId::fromString('milestone-'.Uuid::uuid4()->toString());
    }
}
