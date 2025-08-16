<?php

namespace App\Tests\Domain\Athlete\MaxHeartRate;

use App\Domain\Athlete\MaxHeartRate\DateRangeBased;
use App\Domain\Athlete\MaxHeartRate\InvalidMaxHeartRateFormula;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DateRangeBasedTest extends TestCase
{
    #[DataProvider(methodName: 'provideCalculateData')]
    public function testCalculate(SerializableDateTime $on, int $expectedHeartRate): void
    {
        $dateRangeBased = DateRangeBased::empty()
            ->addRange(SerializableDateTime::fromString('2021-01-01'), 100)
            ->addRange(SerializableDateTime::fromString('2021-02-01'), 110)
            ->addRange(SerializableDateTime::fromString('2021-03-01'), 120);

        $this->assertEquals($expectedHeartRate, $dateRangeBased->calculate(30, $on));
    }

    public function testCalculateItShouldThrowForInvalidDate(): void
    {
        $dateRangeBased = DateRangeBased::empty()
            ->addRange(SerializableDateTime::fromString('2021-01-01'), 100)
            ->addRange(SerializableDateTime::fromString('2021-02-01'), 110)
            ->addRange(SerializableDateTime::fromString('2021-03-01'), 120);

        $this->expectExceptionObject(new InvalidMaxHeartRateFormula('MAX_HEART_RATE_FORMULA: could not determine max heart rate for given date "2020-01-01"'));
        $dateRangeBased->calculate(30, SerializableDateTime::fromString('2020-01-01'));
    }

    public function testAddRangeItShouldThrowOnDuplicate(): void
    {
        $this->expectExceptionObject(new InvalidMaxHeartRateFormula('MAX_HEART_RATE_FORMULA cannot contain the same date more than once'));

        DateRangeBased::empty()
            ->addRange(SerializableDateTime::fromString('2021-01-01'), 100)
            ->addRange(SerializableDateTime::fromString('2021-02-01'), 110)
            ->addRange(SerializableDateTime::fromString('2021-01-01'), 120);
    }

    public static function provideCalculateData(): array
    {
        return [
            [SerializableDateTime::fromString('2021-01-01 13:00:00'), 100],
            [SerializableDateTime::fromString('2021-01-02'), 100],
            [SerializableDateTime::fromString('2021-01-31'), 100],
            [SerializableDateTime::fromString('2021-02-01'), 110],
            [SerializableDateTime::fromString('2021-02-28'), 110],
            [SerializableDateTime::fromString('2021-03-01'), 120],
            [SerializableDateTime::fromString('2025-03-01'), 120],
        ];
    }
}
