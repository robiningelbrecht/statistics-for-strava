<?php

namespace App\Tests\Domain\Activity;

use App\Domain\Activity\Math;
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

    public function testMedianItShouldThrowWhenValuesAreEmpty(): void
    {
        $this->expectExceptionObject(new \InvalidArgumentException('Cannot calculate median of empty array.'));

        Math::median([]);
    }

    #[TestWith(data: [[], []])]
    #[TestWith(data: [[1, 2, 3, 4], [2, 2.5, 2.5, 3]])]
    public function testMovingAverage(array $values, array $expectedResult): void
    {
        $this->assertEquals(
            $expectedResult,
            Math::movingAverage($values, 5)
        );
    }
}
