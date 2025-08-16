<?php

namespace App\Tests\Domain\Athlete\MaxHeartRate;

use App\Domain\Athlete\MaxHeartRate\Gellish;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class GellishTest extends TestCase
{
    #[DataProvider(methodName: 'provideCalculateData')]
    public function testCalculate(int $age, int $expectedHeartRate): void
    {
        $this->assertEquals(
            $expectedHeartRate,
            new Gellish()->calculate($age, SerializableDateTime::fromString('2021-01-01 13:00:00'))
        );
    }

    public static function provideCalculateData(): array
    {
        return [
            [20, 193],
            [30, 186],
            [35, 183],
            [40, 179],
            [50, 172],
            [60, 165],
            [70, 158],
            [80, 151],
            [90, 144],
            [100, 137],
        ];
    }
}
