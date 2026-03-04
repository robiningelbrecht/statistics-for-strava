<?php

namespace App\Tests\Domain\Milestone;

use App\Domain\Milestone\RandomMilestoneIdFactory;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class RandomMilestoneIdFactoryTest extends TestCase
{
    public function testRandom(): void
    {
        $factory = new RandomMilestoneIdFactory();

        $this->assertStringStartsWith(
            'milestone-',
            $factory->random()
        );
        $this->assertTrue(Uuid::isValid(str_replace('milestone-', '', $factory->random())));
    }
}
