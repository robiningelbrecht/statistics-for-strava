<?php

declare(strict_types=1);

namespace App\Domain\Gear\Maintenance\Task\Progress;

use App\Domain\Gear\Maintenance\Task\IntervalUnit;
use App\Infrastructure\Time\Clock\Clock;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class EveryXDaysProgressCalculation implements MaintenanceTaskProgressCalculation
{
    public function __construct(
        private TranslatorInterface $translator,
        private Clock $clock,
    ) {
    }

    public function supports(IntervalUnit $intervalUnit): bool
    {
        return IntervalUnit::EVERY_X_DAYS === $intervalUnit;
    }

    public function calculate(ProgressCalculationContext $context): MaintenanceTaskProgress
    {
        $today = $this->clock->getCurrentDateTimeImmutable();
        $daysSinceLastTagged = $today->diff($context->getLastTaggedOn())->days;

        return MaintenanceTaskProgress::from(
            percentage: min((int) round(($daysSinceLastTagged / $context->getIntervalValue()) * 100), 100),
            description: $this->translator->trans('{daysSinceLastTagged} days', [
                '{daysSinceLastTagged}' => $daysSinceLastTagged,
            ]),
        );
    }
}
