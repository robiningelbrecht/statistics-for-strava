<?php

declare(strict_types=1);

namespace App\Domain\Milestone;

use App\Domain\Milestone\Discoverer\MilestoneDiscoverer;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class MilestoneCollector
{
    /** @var MilestoneDiscoverer[] */
    private array $discoverers;

    /**
     * @param iterable<MilestoneDiscoverer> $discoverers
     */
    public function __construct(
        #[AutowireIterator('app.milestone_discoverer')]
        iterable $discoverers,
    ) {
        $this->discoverers = iterator_to_array($discoverers);
    }

    public function discoverAll(): Milestones
    {
        $milestones = [];

        foreach ($this->discoverers as $discoverer) {
            $milestones = array_merge($milestones, $discoverer->discover()->toArray());
        }

        usort($milestones, fn (Milestone $a, Milestone $b) => $b->getAchievedOn() <=> $a->getAchievedOn());

        return Milestones::fromArray($milestones);
    }
}
