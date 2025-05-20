<?php

namespace App\Tests\Domain\Strava\Athlete;

use App\Domain\Strava\Athlete\AthleteBirthDate;
use PHPUnit\Framework\TestCase;

class AthleteBirthDateTest extends TestCase
{
    public function testFromStringWhenInvalid(): void
    {
        $this->expectExceptionObject(new \InvalidArgumentException('Invalid date "invalid" set in ATHLETE_BIRTHDAY in .env file'));
        AthleteBirthDate::fromString('invalid');
    }
}
