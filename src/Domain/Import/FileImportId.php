<?php

declare(strict_types=1);

namespace App\Domain\Import;

use App\Infrastructure\ValueObject\Identifier\Identifier;

final readonly class FileImportId extends Identifier
{
    public static function getPrefix(): string
    {
        return 'fileImport-';
    }
}
