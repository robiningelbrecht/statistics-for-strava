<?php

namespace App\Tests\Domain\Milestone\Context;

use App\Domain\Milestone\Context\FirstActivityInCountryContext;
use PHPUnit\Framework\TestCase;

class FirstActivityInCountryContextTest extends TestCase
{
    public function testGetCountryNameForStandardCountryCode(): void
    {
        $context = new FirstActivityInCountryContext('be', 'Morning ride');

        $this->assertEquals('Belgium', $context->getCountryName());
    }

    public function testGetCountryNameForKosovo(): void
    {
        $context = new FirstActivityInCountryContext('xk', 'Ride in Kosovo');

        $this->assertEquals('Kosovo', $context->getCountryName());
    }

    public function testGetCountryNameForUppercaseKosovo(): void
    {
        $context = new FirstActivityInCountryContext('XK', 'Ride in Kosovo');

        $this->assertEquals('Kosovo', $context->getCountryName());
    }

    public function testGetCountryCode(): void
    {
        $context = new FirstActivityInCountryContext('be', 'Morning ride');

        $this->assertEquals('be', $context->getCountryCode());
    }

    public function testGetActivityName(): void
    {
        $context = new FirstActivityInCountryContext('be', 'Morning ride');

        $this->assertEquals('Morning ride', $context->getActivityName());
    }
}
