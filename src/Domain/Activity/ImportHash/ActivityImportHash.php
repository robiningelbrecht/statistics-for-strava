<?php

declare(strict_types=1);

namespace App\Domain\Activity\ImportHash;

use App\Domain\Activity\ActivityId;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
final readonly class ActivityImportHash
{
    private function __construct(
        #[ORM\Id, ORM\Column(type: 'string')]
        private string $activityId,
        #[ORM\Column(type: 'string')]
        private string $hash,
    ) {
    }

    public static function fromState(
        ActivityId $activityId,
        string $hash,
    ): self {
        return new self(
            activityId: (string) $activityId,
            hash: $hash,
        );
    }

    public function matches(string $hash): bool
    {
        return $this->hash === $hash;
    }

    public function getActivityId(): string
    {
        return $this->activityId;
    }

    public function getHash(): string
    {
        return $this->hash;
    }
}
