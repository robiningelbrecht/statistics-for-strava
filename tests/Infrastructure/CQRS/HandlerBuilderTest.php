<?php

namespace App\Tests\Infrastructure\CQRS;

use App\Infrastructure\CQRS\HandlerBuilder;
use PHPUnit\Framework\TestCase;

class HandlerBuilderTest extends TestCase
{
    public function testItShouldThrowForHandlerSuffix(): void
    {
        $this->expectExceptionObject(new \InvalidArgumentException('Handler suffix needs to end with "Handler"'));

        new HandlerBuilder('Test');
    }
}
