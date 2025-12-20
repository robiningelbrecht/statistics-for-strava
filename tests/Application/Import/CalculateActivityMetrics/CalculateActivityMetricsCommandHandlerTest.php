<?php

namespace App\Tests\Application\Import\CalculateActivityMetrics;

use App\Application\Import\CalculateActivityMetrics\CalculateActivityMetrics;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Tests\ContainerTestCase;
use App\Tests\SpyOutput;
use Spatie\Snapshots\MatchesSnapshots;

class CalculateActivityMetricsCommandHandlerTest extends ContainerTestCase
{
    use MatchesSnapshots;

    private CommandBus $commandBus;

    public function testHandle(): void
    {
        $output = new SpyOutput();

        $this->commandBus->dispatch(new CalculateActivityMetrics($output));
        $this->assertMatchesTextSnapshot($output);
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->commandBus = $this->getContainer()->get(CommandBus::class);
    }
}
