<?php

namespace App\Tests\Domain\Integration\Notification\Ntfy;

use App\Domain\Integration\Notification\Ntfy\LiveNtfy;
use App\Domain\Integration\Notification\Ntfy\Ntfy;
use App\Domain\Integration\Notification\Ntfy\NtfyPassword;
use App\Domain\Integration\Notification\Ntfy\NtfyUrl;
use App\Domain\Integration\Notification\Ntfy\NtfyUsername;
use App\Infrastructure\ValueObject\String\Url;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

class LiveNtfyTest extends TestCase
{
    use MatchesSnapshots;

    private Ntfy $ntfy;
    private MockObject $client;

    public function testSendNotification(): void
    {
        $this->client
            ->expects($this->once())
            ->method('request')
            ->willReturnCallback(function (string $method, string $path, array $options) {
                $this->assertEquals('POST', $method);
                $this->assertEquals('https://ntfy.com/some-topic', $path);
                $this->assertMatchesJsonSnapshot($options);

                return new Response(200, []);
            });

        $this->ntfy->sendNotification(
            title: 'The title',
            message: 'The message',
            tags: ['+1'],
            click: Url::fromString('https://robiningelbrecht.be'),
            icon: Url::fromString('https://raw.githubusercontent.com/robiningelbrecht/strava-statistics/master/public/assets/images/logo.png')
        );
    }

    public function testSendNotificationWithoutUrl(): void
    {
        $this->ntfy = new LiveNtfy(
            client: $this->client,
            ntfyUrl: null,
            ntfyUsername: null,
            ntfyPassword: null
        );

        $this->client
            ->expects($this->never())
            ->method('request');

        $this->ntfy->sendNotification(
            title: 'The title',
            message: 'The message',
            tags: ['+1'],
            click: null,
            icon: Url::fromString('https://raw.githubusercontent.com/robiningelbrecht/strava-statistics/master/public/assets/images/logo.png')
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = $this->createMock(Client::class);

        $this->ntfy = new LiveNtfy(
            client: $this->client,
            ntfyUrl: NtfyUrl::fromString('https://ntfy.com/some-topic'),
            ntfyUsername: NtfyUsername::fromString('Robin'),
            ntfyPassword: NtfyPassword::fromString('password')
        );
    }
}
