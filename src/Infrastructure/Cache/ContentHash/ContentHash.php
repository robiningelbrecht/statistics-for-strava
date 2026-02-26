<?php

declare(strict_types=1);

namespace App\Infrastructure\Cache\ContentHash;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
final readonly class ContentHash
{
    private function __construct(
        #[ORM\Id, ORM\Column(type: 'string')]
        private string $entityType,
        #[ORM\Id, ORM\Column(type: 'string')]
        private string $entityId,
        #[ORM\Column(type: 'string')]
        private string $hash,
    ) {
    }

    public static function fromState(
        string $entityType,
        string $entityId,
        string $hash,
    ): self {
        return new self(
            entityType: $entityType,
            entityId: $entityId,
            hash: $hash,
        );
    }

    public static function compute(
        string $entityType,
        string $entityId,
        string $content,
    ): self {
        return new self(
            entityType: $entityType,
            entityId: $entityId,
            hash: sha1($content),
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

    public function getHash(): string
    {
        return $this->hash;
    }

    public function equals(self $other): bool
    {
        return $this->hash === $other->hash;
    }
}
