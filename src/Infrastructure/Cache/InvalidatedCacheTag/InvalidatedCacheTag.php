<?php

declare(strict_types=1);

namespace App\Infrastructure\Cache\InvalidatedCacheTag;

use App\Infrastructure\Cache\Tag;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
final readonly class InvalidatedCacheTag
{
    private function __construct(
        #[ORM\Id, ORM\Column(type: 'string')]
        private string $tag,
    ) {
    }

    public static function fromState(
        Tag $tag,
    ): self {
        return new self(
            tag: (string) $tag,
        );
    }

    public function getTag(): string
    {
        return $this->tag;
    }
}
