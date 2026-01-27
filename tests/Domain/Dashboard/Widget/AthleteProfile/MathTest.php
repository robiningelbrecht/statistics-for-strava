<?php

namespace App\Tests\Domain\Dashboard\Widget\AthleteProfile;

use App\Domain\Dashboard\Widget\AthleteProfile\Math;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class MathTest extends TestCase
{
    #[TestWith(data: [[1, 2, 3], 2])]
    #[TestWith(data: [[1, 2, 3, 4], 2.5])]
    public function testMedian(array $values, float $expectedResult): void
    {
        $this->assertEquals(
            $expectedResult,
            Math::median($values)
        );
    }
}
