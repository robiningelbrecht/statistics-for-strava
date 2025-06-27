<?php

namespace App\Tests\Infrastructure\ValueObject\Measurement\Mass;

use App\Infrastructure\ValueObject\Measurement\Mass\Gram;
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
}
