<?php

namespace App\Tests\Domain\Activity;

use App\Domain\Activity\ProvideSteppedValue;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ProvideSteppedValueTest extends TestCase
{
    #[DataProvider(methodName: 'provideTestData')]
    public function testFindClosestSteppedValue(int $min, int $max, int $step, int|float $target, int $expectedOutcome): void
    {
        $class = new class($min, $max, $step, $target) {
            use ProvideSteppedValue;

            public function __construct(
                private readonly int $min,
                private readonly int $max,
                private readonly int $step,
                private readonly int $target)
            {
            }

            public function getClosestSteppedValue(): int
            {
                return $this->findClosestSteppedValue(
                    min: $this->min,
                    max: $this->max,
                    step: $this->step,
                    target: $this->target
                );
            }
        };

        $this->assertEquals(
            $expectedOutcome,
            new $class($min, $max, $step, $target)->getClosestSteppedValue()
        );
    }

    public static function provideTestData(): iterable
    {
        yield [0, 100, 3, 2, 3];
        yield [10, 100, 3, 2, 10];
        yield [10, 20, 3, 23, 20];
    }
}
