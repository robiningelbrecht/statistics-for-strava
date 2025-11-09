<?php

namespace App\Tests\Domain\Integration\GitHub;

use App\BuildApp\AppVersion;
use App\Domain\Integration\GitHub\AppUpdateAvailableNotificationCronAction;
use App\Domain\Integration\GitHub\GitHub;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\Serialization\Json;
use App\Tests\Infrastructure\CQRS\Command\Bus\SpyCommandBus;
use App\Tests\SpyOutput;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

class AppUpdateAvailableNotificationCronActionTest extends TestCase
{
    use MatchesSnapshots;
    private AppUpdateAvailableNotificationCronAction $cronAction;
    private CommandBus $commandBus;
    private Client $client;

    public function testRun(): void
    {
        $this->client
            ->expects($this->once())
            ->method('request')
            ->with('GET', 'https://api.github.com/repos/robiningelbrecht/statistics-for-strava/releases/latest')
            ->willReturn(new Response(status: 200, body: Json::encode(['name' => 'v3.8.0'])));

        $this->cronAction->run(new SpyOutput());
        $this->assertMatchesJsonSnapshot(Json::encode($this->commandBus->getDispatchedCommands()));
    }

    public function testRunWhenSameVersions(): void
    {
        $this->client
            ->expects($this->once())
            ->method('request')
            ->with('GET', 'https://api.github.com/repos/robiningelbrecht/statistics-for-strava/releases/latest')
            ->willReturn(new Response(status: 200, body: Json::encode(['name' => AppVersion::getSemanticVersion()])));

        $this->cronAction->run(new SpyOutput());
        $this->assertEmpty($this->commandBus->getDispatchedCommands());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = $this->createMock(Client::class);

        $this->cronAction = new AppUpdateAvailableNotificationCronAction(
            new GitHub($this->client),
            $this->commandBus = new SpyCommandBus(),
        );
    }
}
