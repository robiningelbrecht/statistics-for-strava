<?php

declare(strict_types=1);

namespace App\Domain\Import\FileParser;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.activity_file.parser')]
interface ActivityFileParser
{
    public function supportedExtension(): string;

    public function parse(RawActivityFile $file): ParsedActivityFile;
}
