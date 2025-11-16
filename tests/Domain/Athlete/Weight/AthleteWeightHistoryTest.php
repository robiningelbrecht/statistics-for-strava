<?php

namespace App\Tests\Domain\Athlete\Weight;

use App\Domain\Athlete\Weight\AthleteWeight;
use App\Domain\Athlete\Weight\AthleteWeightHistory;
use App\Infrastructure\ValueObject\Measurement\Mass\Kilogram;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use PHPUnit\Framework\TestCase;

class AthleteWeightHistoryTest extends TestCase
{
    public function testFind(): void
    {
        $weightHistory = AthleteWeightHistory::fromArray([
            '2024-01-01' => 220,
            '2024-02-02' => 221,
            '2024-04-04' => 223,
            '2024-03-03' => 222,
        ], UnitSystem::METRIC);

        $this->assertEquals(
            AthleteWeight::fromState(
                on: SerializableDateTime::fromString('2024-04-04'),
                weightInGrams: Kilogram::from(223)->toGram(),
            ),
            $weightHistory->find(SerializableDateTime::fromString('2024-04-04'))
        );
        $this->assertEquals(
            AthleteWeight::fromState(
                on: SerializableDateTime::fromString('2024-04-04'),
                weightInGrams: Kilogram::from(223)->toGram(),
            ),
            $weightHistory->find(SerializableDateTime::fromString('2025-01-01'))
        );
    }

    public function testItShouldThrowOnInvalidWeight(): void
    {
        $this->expectExceptionObject(new \InvalidArgumentException('Invalid weight "lol" set for athlete weightHistory in config.yaml file'));
        AthleteWeightHistory::fromArray(['2025-11-16' => 'lol'], UnitSystem::METRIC);
    }

    public function testItShouldThrowOnInvalidDate(): void
    {
        $this->expectExceptionObject(new \InvalidArgumentException('Invalid date "YYYY-MM-DD" set for athlete weightHistory in config.yaml file'));
        AthleteWeightHistory::fromArray(['YYYY-MM-DD' => 220], UnitSystem::METRIC);
    }
}
