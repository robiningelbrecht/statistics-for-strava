<?php

namespace App\Tests\Infrastructure\ValueObject\Measurement\Mass;

use App\Infrastructure\ValueObject\Measurement\Mass\Gram;
use App\Infrastructure\ValueObject\Measurement\Mass\Pound;
use PHPUnit\Framework\TestCase;

class PoundTest extends TestCase
{
    public function testToMetric(): void
    {
        $this->assertEquals(
            Gram::zero(),
            Pound::zero()->toGram(),
        );
    }
}
