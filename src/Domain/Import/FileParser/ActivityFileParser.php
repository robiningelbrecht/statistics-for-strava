<?php

declare(strict_types=1);

namespace App\Domain\Import\FileParser;

use App\Domain\Import\SupportedFileExtension;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.activity_file.parser')]
interface ActivityFileParser
{
    public function supportedExtension(): SupportedFileExtension;

    public function parse(RawActivityFile $file): ParsedActivityFile;
}
