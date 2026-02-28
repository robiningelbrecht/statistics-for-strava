<?php

declare(strict_types=1);

namespace App\Domain\Milestone;

use App\Infrastructure\ValueObject\Collection;

/**
 * @extends Collection<Milestone>
 */
final class Milestones extends Collection
{
    public function getItemClassName(): string
    {
        return Milestone::class;
    }

    /**
     * @return MilestoneFilterGroup[]
     */
    public function getUniqueFilterGroups(): array
    {
        $groups = [];
        /** @var Milestone $milestone */
        foreach ($this as $milestone) {
            $group = $milestone->getCategory()->getFilterGroup();
            $groups[$group->value] = $group;
        }

        ksort($groups);

        return array_values($groups);
    }
}
