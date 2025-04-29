<?php

namespace App\Tests\Domain\Strava\Athlete\MaxHeartRate;

use App\Domain\Strava\Athlete\MaxHeartRate\Arena;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ArenaTest extends TestCase
{
    #[DataProvider(methodName: 'provideCalculateData')]
    public function testCalculate(int $age, int $expectedHeartRate): void
    {
        $this->assertEquals(
            $expectedHeartRate,
            new Arena()->calculate($age, SerializableDateTime::fromString('2021-01-01 13:00:00'))
        );
    }

    public static function provideCalculateData(): array
    {
        return [
            [20, 195],
            [30, 188],
            [35, 184],
            [40, 181],
            [50, 173],
            [60, 166],
            [70, 159],
            [80, 152],
            [90, 145],
            [100, 137],
        ];
    }
}
