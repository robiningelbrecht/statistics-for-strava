<?php

declare(strict_types=1);

namespace App\Infrastructure\CQRS\Command\Deserialize;

trait ProvidesCommandName
{
    public static function getCommandName(): string
    {
        $shortName = new \ReflectionClass(static::class)->getShortName();

        return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '-$0', $shortName));
    }
}
