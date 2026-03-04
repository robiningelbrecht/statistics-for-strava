<?php

declare(strict_types=1);

namespace App\Domain\Milestone;

interface MilestoneIdFactory
{
    public function random(): MilestoneId;
}
