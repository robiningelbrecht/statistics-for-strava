<?php

declare(strict_types=1);

namespace App\Infrastructure\CQRS\Command;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.command_handler')]
interface CommandHandler
{
    public function handle(Command $command): void;
}
