<?php

namespace App\Tests\Domain\Strava\Athlete\MaxHeartRate;

use App\Domain\Strava\Athlete\MaxHeartRate\Fox;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FoxTest extends TestCase
{
    #[DataProvider(methodName: 'provideCalculateData')]
    public function testCalculate(int $age, int $expectedHeartRate): void
    {
        $this->assertEquals(
            $expectedHeartRate,
            new Fox()->calculate($age, SerializableDateTime::fromString('2021-01-01 13:00:00'))
        );
    }

    public static function provideCalculateData(): array
    {
        return [
            [20, 200],
            [30, 190],
            [35, 185],
            [40, 180],
            [50, 170],
            [60, 160],
            [70, 150],
            [80, 140],
            [90, 130],
            [100, 120],
        ];
    }
}
