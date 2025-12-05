<?php

namespace App\Tests\Application;

use App\Application\AppUrl;
use App\Application\importDataAndBuildAppCronAction;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\Serialization\Json;
use App\Tests\Console\ConsoleOutputSnapshotDriver;
use App\Tests\ContainerTestCase;
use App\Tests\Infrastructure\CQRS\Command\Bus\SpyCommandBus;
use App\Tests\Infrastructure\Time\Clock\PausedClock;
use App\Tests\Infrastructure\Time\ResourceUsage\FixedResourceUsage;
use App\Tests\SpySymfonyStyleOutput;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;

class importDataAndBuildAppCronActionTest extends ContainerTestCase
{
    use MatchesSnapshots;

    private importDataAndBuildAppCronAction $importAndBuildAppCronAction;
    private CommandBus $commandBus;

    public function testRun(): void
    {
        $output = new SpySymfonyStyleOutput(new StringInput('input'), new NullOutput());
        $this->importAndBuildAppCronAction->run($output);

        $this->assertMatchesJsonSnapshot(Json::encode($this->commandBus->getDispatchedCommands()));
        $this->assertMatchesSnapshot($output, new ConsoleOutputSnapshotDriver());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->importAndBuildAppCronAction = new importDataAndBuildAppCronAction(
            $this->commandBus = new SpyCommandBus(),
            new FixedResourceUsage(),
            AppUrl::fromString('http://localhost'),
            PausedClock::fromString('2025-11-10 15:24')
        );
    }
}
