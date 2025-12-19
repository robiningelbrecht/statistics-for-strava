<?php

namespace App\Tests\Application\Import\ImportSegments;

use App\Application\Import\ImportSegments\ImportSegments;
use App\Domain\Segment\SegmentId;
use App\Domain\Segment\SegmentRepository;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\ValueObject\String\Name;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Segment\SegmentBuilder;
use App\Tests\SpyOutput;
use Spatie\Snapshots\MatchesSnapshots;

class ImportSegmentsCommandHandlerTest extends ContainerTestCase
{
    use MatchesSnapshots;

    private CommandBus $commandBus;

    public function testHandle(): void
    {
        $output = new SpyOutput();

        $this->getContainer()->get(SegmentRepository::class)->add(
            SegmentBuilder::fromDefaults()
                ->withSegmentId(SegmentId::fromUnprefixed('1'))
                ->withName(Name::fromString('⭐️ Segment'))
                ->withIsFavourite(false)
                ->build()
        );
        $this->getContainer()->get(SegmentRepository::class)->add(
            SegmentBuilder::fromDefaults()
                ->withSegmentId(SegmentId::fromUnprefixed('2'))
                ->withName(Name::fromString('Segment One'))
                ->withIsFavourite(false)
                ->build()
        );

        $this->commandBus->dispatch(new ImportSegments($output));
        $this->assertMatchesTextSnapshot($output);
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->getContainer()->get(CommandBus::class);
        $this->getConnection()->executeStatement(
            'INSERT INTO KeyValue (`key`, `value`) VALUES (:key, :value)',
            ['key' => 'lock.importDataOrBuildApp', 'value' => '{"lockAcquiredBy": "test"}']
        );
    }
}
