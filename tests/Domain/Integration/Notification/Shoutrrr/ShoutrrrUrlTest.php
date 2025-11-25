<?php

namespace App\Tests\Domain\Integration\Notification\Shoutrrr;

use App\Domain\Integration\Notification\Shoutrrr\ShoutrrrUrl;
use PHPUnit\Framework\TestCase;

class ShoutrrrUrlTest extends TestCase
{
    public function testWithParams(): void
    {
        $this->assertEquals(
            'ntfy://ntfy.sh/topic',
            (string) ShoutrrrUrl::fromString('ntfy://ntfy.sh/topic')->withParams([])
        );

        $this->assertEquals(
            'ntfy://ntfy.sh/topic?click=the-click',
            (string) ShoutrrrUrl::fromString('ntfy://ntfy.sh/topic')->withParams(['click' => 'the-click'])
        );
    }

    public function testFromDeprecatedNtfyConfig(): void
    {
        $this->assertEquals(
            'ntfy://user:pass@ntfy.sh/topic',
            (string) ShoutrrrUrl::fromDeprecatedNtfyConfig(
                ntfyUrl: 'https://ntfy.sh/topic',
                ntfyUsername: 'user',
                ntfyPassword: 'pass',
            )
        );

        $this->assertEquals(
            'ntfy://ntfy.sh/topic',
            (string) ShoutrrrUrl::fromDeprecatedNtfyConfig(
                ntfyUrl: 'https://ntfy.sh/topic',
                ntfyUsername: null,
                ntfyPassword: null,
            )
        );
    }
}
