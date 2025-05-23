<?php

namespace App\Infrastructure\ValueObject\String;

enum PlatformEnvironment: string
{
    case DEV = 'dev';
    case TEST = 'test';
    case PROD = 'prod';

    public function isTest(): bool
    {
        return self::TEST === $this;
    }
}
