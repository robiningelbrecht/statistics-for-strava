<?php

declare(strict_types=1);

namespace App\Domain\Milestone;

use App\Domain\Milestone\Discoverer\MilestoneDiscoverer;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class MilestoneCollector
{
    /** @var MilestoneDiscoverer[] */
    private array $discoverers;
    private Milestones $milestones;

    /**
     * @param iterable<MilestoneDiscoverer> $discoverers
     */
    public function __construct(
        #[AutowireIterator('app.milestone_discoverer')]
        iterable $discoverers,
    ) {
        $this->discoverers = iterator_to_array($discoverers);
        $this->milestones = Milestones::empty();
    }

    public function discoverAll(): Milestones
    {
        if (!$this->milestones->isEmpty()) {
            return $this->milestones;
        }

        $milestones = [];

        foreach ($this->discoverers as $discoverer) {
            $milestones = array_merge($milestones, $discoverer->discover()->toArray());
        }

        usort($milestones, fn (Milestone $a, Milestone $b): int => $b->getAchievedOn() <=> $a->getAchievedOn());

        $this->milestones->addMultiple($milestones);

        return $this->milestones;
    }
}
