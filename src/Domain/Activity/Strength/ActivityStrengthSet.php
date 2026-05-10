<?php

declare(strict_types=1);

namespace App\Domain\Activity\Strength;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
final readonly class ActivityStrengthSet
{
    private function __construct(
        #[ORM\Id, ORM\Column(type: 'string')]
        private string $activityId,
        #[ORM\Id, ORM\Column(type: 'integer')]
        private int $position,
        #[ORM\Column(type: 'string')]
        private string $exerciseName,
        #[ORM\Column(type: 'integer')]
        private int $numberOfSets,
        #[ORM\Column(type: 'integer')]
        private int $numberOfReps,
        #[ORM\Column(type: 'float', nullable: true)]
        private ?float $weightLbs,
        #[ORM\Column(type: 'float', nullable: true)]
        private ?float $estimatedOneRepMax,
    ) {
    }
}
