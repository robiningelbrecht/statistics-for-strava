<?php

namespace App\Tests\Domain\Athlete\MaxHeartRate;

use App\Domain\Athlete\MaxHeartRate\Astrand;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AstrandTest extends TestCase
{
    #[DataProvider(methodName: 'provideCalculateData')]
    public function testCalculate(int $age, int $expectedHeartRate): void
    {
        $this->assertEquals(
            $expectedHeartRate,
            new Astrand()->calculate($age, SerializableDateTime::fromString('2021-01-01 13:00:00'))
        );
    }

    public static function provideCalculateData(): array
    {
        return [
            [20, 200],
            [30, 191],
            [35, 187],
            [40, 183],
            [50, 175],
            [60, 166],
            [70, 158],
            [80, 149],
            [90, 141],
            [100, 133],
        ];
    }
}
