<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueObject\String;

final readonly class Path extends NonEmptyStringLiteral
{
    public function getExtension(): string
    {
        $extension = pathinfo((string) $this, PATHINFO_EXTENSION);
        return strtolower($extension);
    }

    public function getFilename(): string
    {
        $filename = pathinfo((string) $this, PATHINFO_FILENAME);
        return sprintf('%s.%s', $filename, $this->getExtension());
    }
}
