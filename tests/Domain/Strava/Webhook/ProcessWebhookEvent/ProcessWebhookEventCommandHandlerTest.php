<?php

namespace App\Tests\Domain\Strava\Webhook\ProcessWebhookEvent;

use App\Domain\Strava\Webhook\ProcessWebhookEvent\ProcessWebhookEvent;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\Serialization\Json;
use App\Tests\ContainerTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class ProcessWebhookEventCommandHandlerTest extends ContainerTestCase
{
    use MatchesSnapshots;

    private CommandBus $commandBus;

    public function testHandle(): void
    {
        $this->commandBus->dispatch(new ProcessWebhookEvent([
            'object_id' => 1,
            'object_type' => 'activity',
        ]));

        $this->assertMatchesJsonSnapshot(
            Json::encode($this->getConnection()->executeQuery('SELECT * FROM StravaWebhookEvent')->fetchAllAssociative())
        );
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->getContainer()->get(CommandBus::class);
    }
}
