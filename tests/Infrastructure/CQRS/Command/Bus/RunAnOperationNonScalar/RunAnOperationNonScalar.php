<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\CQRS\Command\Bus\RunAnOperationNonScalar;

use App\Infrastructure\CQRS\Command\DomainCommand;
use App\Infrastructure\ValueObject\String\Name;

final readonly class RunAnOperationNonScalar extends DomainCommand
{
    public function __construct(
        private readonly Name $value,
    ) {
    }
}
