<?php

declare(strict_types=1);

namespace App\Tests\Domain\App;

use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Tests\ContainerTestCase;
use App\Tests\Infrastructure\FileSystem\provideAssertFileSystem;
use App\Tests\ProvideTestData;

abstract class BuildAppFilesTestCase extends ContainerTestCase
{
    use ProvideTestData;
    use provideAssertFileSystem;

    private string $snapshotName;
    protected CommandBus $commandBus;

    protected function getSnapshotId(): string
    {
        return new \ReflectionClass($this)->getShortName().'--'.
            $this->name().'--'.
            $this->snapshotName;
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->getContainer()->get(CommandBus::class);
    }
}
