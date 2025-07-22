<?php

namespace App\Infrastructure\ValueObject\String;

final readonly class KernelProjectDir extends NonEmptyStringLiteral
{
    public function getForTestSuite(?string $subDir): self
    {
        if (is_null($subDir)) {
            return self::fromString($this.'/tests');
        }

        return self::fromString($this.'/tests/'.$subDir);
    }
}
