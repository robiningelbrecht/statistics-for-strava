<?php

namespace App\Tests\Domain\Athlete\MaxHeartRate;

use App\Domain\Athlete\MaxHeartRate\Nes;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class NesTest extends TestCase
{
    #[DataProvider(methodName: 'provideCalculateData')]
    public function testCalculate(int $age, int $expectedHeartRate): void
    {
        $this->assertEquals(
            $expectedHeartRate,
            new Nes()->calculate($age, SerializableDateTime::fromString('2021-01-01 13:00:00'))
        );
    }

    public static function provideCalculateData(): array
    {
        return [
            [20, 198],
            [30, 192],
            [35, 189],
            [40, 185],
            [50, 179],
            [60, 173],
            [70, 166],
            [80, 160],
            [90, 153],
            [100, 147],
        ];
    }
}
