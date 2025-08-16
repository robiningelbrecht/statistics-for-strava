<?php

declare(strict_types=1);

namespace App\Domain\Gear\CustomGear;

use App\Domain\Gear\ImportedGear\ImportedGear;
use App\Infrastructure\ValueObject\String\Tag;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
final class CustomGear extends ImportedGear
{
    private Tag $fullTag;

    public function withFullTag(Tag $fullTag): self
    {
        $new = clone $this;
        $new->fullTag = $fullTag;

        return $new;
    }

    public function getTag(): string
    {
        return (string) $this->fullTag;
    }
}
