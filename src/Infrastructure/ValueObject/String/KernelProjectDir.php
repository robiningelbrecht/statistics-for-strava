<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueObject\String;

final readonly class KernelProjectDir extends NonEmptyStringLiteral
{
    public function getConfigBasePath(): string
    {
        return $this.'/config/app/';
    }
}
