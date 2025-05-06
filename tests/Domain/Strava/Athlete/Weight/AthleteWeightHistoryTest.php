<?php

namespace App\Tests\Domain\Strava\Athlete\Weight;

use App\Domain\Strava\Athlete\Weight\AthleteWeightHistory;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use PHPUnit\Framework\TestCase;

class AthleteWeightHistoryTest extends TestCase
{
    public function testItShouldThrow(): void
    {
        $this->expectExceptionObject(new \InvalidArgumentException('Invalid ATHLETE_WEIGHT_HISTORY detected in .env file. Make sure the string is valid JSON'));
        AthleteWeightHistory::fromString('{"lol}', UnitSystem::METRIC);
    }

    public function testItShouldThrowOnInvalidDate(): void
    {
        $this->expectExceptionObject(new \InvalidArgumentException('Invalid date "YYYY-MM-DD" set in ATHLETE_WEIGHT_HISTORY in .env file'));
        AthleteWeightHistory::fromString('{"YYYY-MM-DD": 220}', UnitSystem::METRIC);
    }
}
