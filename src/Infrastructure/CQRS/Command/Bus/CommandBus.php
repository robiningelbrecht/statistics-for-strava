<?php

declare(strict_types=1);

namespace App\Infrastructure\CQRS\Command\Bus;

use App\Infrastructure\CQRS\Command\Command;

interface CommandBus
{
    public function dispatch(Command $command): void;
}
