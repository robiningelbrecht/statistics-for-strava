<?php

namespace App\Tests\Infrastructure\Http;

use App\Infrastructure\Http\ServerSentEvent;
use PHPUnit\Framework\TestCase;

class ServerSentEventTest extends TestCase
{
    public function testToString(): void
    {
        $this->assertEquals(
            'event: error
data: WAW ERROR

',
            (string) new ServerSentEvent(
                eventName: 'error',
                data: 'WAW ERROR'
            )
        );
    }
}
