<?php

declare(strict_types=1);

namespace App\Application\Import\ImportSegments;

final readonly class OptInToSegmentDetailsImport
{
    private function __construct(
        private bool $flag,
    ) {
    }

    public static function fromBool(bool $flag): self
    {
        return new self($flag);
    }

    public function hasOptedIn(): bool
    {
        return $this->flag;
    }
}
