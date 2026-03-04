<?php

declare(strict_types=1);

namespace App\Tests\Domain\Milestone;

use App\Domain\Milestone\MilestoneId;
use App\Domain\Milestone\MilestoneIdFactory;

final class IncrementingMilestoneIdFactory implements MilestoneIdFactory
{
    private int $counter = 0;

    public function random(): MilestoneId
    {
        return MilestoneId::fromString('milestone-'.++$this->counter);
    }
}
