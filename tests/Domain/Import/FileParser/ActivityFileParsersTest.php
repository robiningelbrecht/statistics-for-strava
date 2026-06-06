<?php

declare(strict_types=1);

namespace App\Tests\Domain\Import\FileParser;

use App\Application\Import\FileImport\Pipeline\ActivityImportContext;use App\Domain\Import\FileParser\ActivityFileParser;use App\Domain\Import\FileParser\ActivityFileParsers;use App\Domain\Import\FileParser\CouldNotParseActivityFile;use PHPUnit\Framework\TestCase;

class ActivityFileParsersTest extends TestCase
{
    private ActivityFileParser $fitParser;
    private ActivityFileParser $tcxParser;
    private ActivityFileParsers $registry;

    public function testGetForFileResolvesByExtensionCaseInsensitive(): void
    {
        $this->assertSame($this->fitParser, $this->registry->getForFile('/import/activity.FIT'));
        $this->assertSame($this->tcxParser, $this->registry->getForFile('/import/sub/dir/ride.tcx'));
    }

    public function testGetForFileWithoutExtensionThrows(): void
    {
        $this->expectException(CouldNotParseActivityFile::class);
        $this->registry->getForFile('/import/activity');
    }

    public function testGetForFileWithUnsupportedExtensionThrows(): void
    {
        $this->expectException(CouldNotParseActivityFile::class);
        $this->registry->getForFile('/import/activity.gpx');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->fitParser = $this->createParser(['fit']);
        $this->tcxParser = $this->createParser(['tcx']);
        $this->registry = new ActivityFileParsers([$this->fitParser, $this->tcxParser]);
    }

    /**
     * @param string[] $extensions
     */
    private function createParser(array $extensions): ActivityFileParser
    {
        return new class($extensions) implements ActivityFileParser {
            /**
             * @param string[] $extensions
             */
            public function __construct(private readonly array $extensions)
            {
            }

            public function supportedExtension(): array
            {
                return $this->extensions;
            }

            public function parse(string $absolutePath, string $originalFilename): ActivityImportContext
            {
                throw new \LogicException('Not needed for this test');
            }
        };
    }
}
