<?php

namespace App\Tests\Infrastructure\CQRS\Command\Bus;

use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;

class RunOperationWithInvalidNameHandler implements CommandHandler
{
    public function handle(Command $command): void
    {
    }
}
