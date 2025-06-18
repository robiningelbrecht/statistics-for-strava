<?php

namespace App\Tests\Domain\Strava\Athlete;

use App\Domain\Strava\Athlete\AthleteBirthDate;
use PHPUnit\Framework\TestCase;

class AthleteBirthDateTest extends TestCase
{
    public function testFromStringWhenInvalid(): void
    {
        $this->expectExceptionObject(new \InvalidArgumentException('Invalid date "invalid" set for athlete birthday in config.yaml file'));
        AthleteBirthDate::fromString('invalid');
    }
}
