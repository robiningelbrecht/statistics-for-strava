<?php

namespace App\Tests\Infrastructure\CQRS\Command\Bus\RunAnOperationThatRequiresRebuild;

use App\Infrastructure\CQRS\Command\DomainCommand;
use App\Infrastructure\CQRS\Command\RequiresRebuild;

#[RequiresRebuild]
final readonly class RunAnOperationThatRequiresRebuild extends DomainCommand
{
}
