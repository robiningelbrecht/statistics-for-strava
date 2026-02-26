<?php

declare(strict_types=1);

namespace App\Infrastructure\Cache\CacheTagDependency;

use App\Infrastructure\Cache\Tag;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Index(name: 'CacheTagDependency_dependsOnTag', columns: ['dependsOnTag'])]
final readonly class CacheTagDependency
{
    private function __construct(
        #[ORM\Id, ORM\Column(type: 'string')]
        private string $entityType,
        #[ORM\Id, ORM\Column(type: 'string')]
        private string $entityId,
        #[ORM\Id, ORM\Column(type: 'string')]
        private string $dependsOnTag,
    ) {
    }

    public static function fromState(
        string $entityType,
        string $entityId,
        Tag $dependsOnTag,
    ): self {
        return new self(
            entityType: $entityType,
            entityId: $entityId,
            dependsOnTag: (string) $dependsOnTag,
        );
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function getEntityId(): string
    {
        return $this->entityId;
    }

    public function getDependsOnTag(): string
    {
        return $this->dependsOnTag;
    }
}
