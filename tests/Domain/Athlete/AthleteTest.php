<?php

namespace App\Tests\Domain\Athlete;

use App\Domain\Athlete\Athlete;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AthleteTest extends TestCase
{
    #[DataProvider(methodName: 'provideDataAthleteAgeData')]
    public function testGetAthleteAge(
        SerializableDateTime $on,
        SerializableDateTime $athleteBirthday,
        int $expectedAge): void
    {
        $athlete = Athlete::create(
            data: [
                'birthDate' => $athleteBirthday->format('Y-m-d'),
            ],
        );

        $this->assertEquals(
            $expectedAge,
            $athlete->getAgeInYears($on)
        );
    }

    public function testGetMaxHeartRateItShouldThrowWhenStrategyNotSet(): void
    {
        $this->expectExceptionObject(new \RuntimeException('Max heart rate formula not set'));

        $athlete = Athlete::create(
            data: [
                'birthDate' => '2024-01-01',
            ],
        );
        $athlete->getMaxHeartRate(SerializableDateTime::fromString('2024-01-01'));
    }

    public static function provideDataAthleteAgeData(): array
    {
        return [
            [SerializableDateTime::fromString('2023-08-13'), SerializableDateTime::fromString('1989-08-14'), 33],
            [SerializableDateTime::fromString('2023-08-14'), SerializableDateTime::fromString('1989-08-14'), 34],
            [SerializableDateTime::fromString('2023-08-15'), SerializableDateTime::fromString('1989-08-14'), 34],
        ];
    }
}
