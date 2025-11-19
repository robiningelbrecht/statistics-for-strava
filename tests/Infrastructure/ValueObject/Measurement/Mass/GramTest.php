<?php

namespace App\Tests\Infrastructure\ValueObject\Measurement\Mass;

use App\Infrastructure\ValueObject\Measurement\Mass\Gram;
use App\Infrastructure\ValueObject\Measurement\Mass\Kilogram;
use App\Infrastructure\ValueObject\Measurement\Mass\Pound;
use PHPUnit\Framework\TestCase;

class GramTest extends TestCase
{
    public function testToImperial(): void
    {
        $this->assertEquals(
            Pound::zero(),
            Gram::zero()->toImperial(),
        );
    }

    public function testToKilogram(): void
    {
        $this->assertEquals(
            Kilogram::from(1),
            Gram::from(1000)->toKilogram(),
        );
    }
}
