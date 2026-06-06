<?php

declare(strict_types=1);

namespace App\Domain\Import\FileParser;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class ActivityFileParsers
{
    /**
     * @param iterable<ActivityFileParser> $parsers
     */
    public function __construct(
        #[AutowireIterator('app.activity_file.parser')]
        private iterable $parsers,
    ) {
    }

    public function parse(RawActivityFile $file): ParsedActivityFile
    {
        $extension = $file->getPath()->getExtension();
        if ('' === $extension) {
            throw new CouldNotParseActivityFile(message: sprintf('Could not determine file extension for "%s"', $file->getPath()), activityFile: $file);
        }

        foreach ($this->parsers as $parser) {
            if ($extension === $parser->supportedExtension()) {
                return $parser->parse($file);
            }
        }

        throw new UnsupportedFileType(sprintf('No parser available for file extension ".%s"', $file->getPath()));
    }
}
