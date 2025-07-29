<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueObject\Measurement\Velocity;

use App\Infrastructure\ValueObject\Measurement\Unit;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;

interface Pace extends Unit, Velocity
{
    public function toUnitSystem(UnitSystem $unitSystem): self;
}
