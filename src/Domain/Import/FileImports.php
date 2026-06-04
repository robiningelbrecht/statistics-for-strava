<?php

declare(strict_types=1);

namespace App\Domain\Import;

use App\Infrastructure\ValueObject\Collection;

/**
 * @extends Collection<FileImport>
 */
class FileImports extends Collection
{
    public function getItemClassName(): string
    {
        return FileImport::class;
    }
}
