<?php

declare(strict_types=1);

namespace App\Domain\Milestone\Context;

use App\Domain\Milestone\MilestoneContext;

final readonly class EddingtonContext implements MilestoneContext
{
    public function __construct(
        public int $number,
    ) {
    }
}
