<?php

declare(strict_types=1);

namespace App\Domain\Milestone\Discoverer;

use App\Domain\Milestone\Milestones;

final readonly class FirstsMilestoneDiscoverer implements MilestoneDiscoverer
{
    public function discover(): Milestones
    {
        return Milestones::empty();
    }
}
