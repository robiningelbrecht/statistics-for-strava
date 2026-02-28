<?php

declare(strict_types=1);

namespace App\Domain\Milestone\Context;

use App\Domain\Milestone\MilestoneContext;

final readonly class PersonalBestContext implements MilestoneContext
{
    public function __construct(
        public string $metric,
        public string $value,
        public ?string $previousValue,
    ) {
    }
}
