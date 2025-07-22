<?php

declare(strict_types=1);

namespace App\Domain\Strava\Rewind\FindCarbonSaved;

use App\Infrastructure\CQRS\Query\Response;
use App\Infrastructure\ValueObject\Measurement\Mass\Kilogram;

final readonly class FindCarbonSavedResponse implements Response
{
    public function __construct(
        private Kilogram $kgCoCarbonSaved,
    ) {
    }

    public function getKgCoCarbonSaved(): Kilogram
    {
        return $this->kgCoCarbonSaved;
    }
}
