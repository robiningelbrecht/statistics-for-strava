<?php

declare(strict_types=1);

namespace App\Tests\Domain\Import\FileParser;

use App\Domain\Import\FileParser\ActivityFileParser;
use App\Domain\Import\FileParser\ActivityFileParsers;
use App\Domain\Import\FileParser\CouldNotParseActivityFile;
use App\Domain\Import\FileParser\ParsedActivityFile;
use App\Domain\Import\FileParser\RawActivityFile;
use App\Domain\Import\FileParser\UnsupportedFileType;
use App\Infrastructure\ValueObject\String\Path;
use PHPUnit\Framework\TestCase;

class ActivityFileParsersTest extends TestCase
{
    private ActivityFileParsers $registry;

    public function testParseRoutesToFitParserCaseInsensitive(): void
    {
        $this->expectExceptionObject(new \LogicException('parsed-by-fit'));

        $this->registry->parse($this->rawFile('/import/activity.FIT'));
    }

    public function testParseRoutesToTcxParser(): void
    {
        $this->expectExceptionObject(new \LogicException('parsed-by-tcx'));

        $this->registry->parse($this->rawFile('/import/sub/dir/ride.tcx'));
    }

    public function testParseRoutesToGpxParser(): void
    {
        $this->expectExceptionObject(new \LogicException('parsed-by-gpx'));

        $this->registry->parse($this->rawFile('/import/sub/dir/ride.gpx'));
    }

    public function testParseWithoutExtensionThrows(): void
    {
        $this->expectException(CouldNotParseActivityFile::class);
        $this->registry->parse($this->rawFile('/import/activity'));
    }

    public function testParseWithUnsupportedExtensionThrows(): void
    {
        $this->expectException(UnsupportedFileType::class);
        $this->registry->parse($this->rawFile('/import/activity.lol'));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->registry = new ActivityFileParsers([
            $this->createParser('fit'),
            $this->createParser('tcx'),
            $this->createParser('gpx'),
        ]);
    }

    private function rawFile(string $path): RawActivityFile
    {
        return RawActivityFile::from(Path::fromString($path), '');
    }

    private function createParser(string $extension): ActivityFileParser
    {
        return new readonly class($extension) implements ActivityFileParser {
            public function __construct(private string $extension)
            {
            }

            public function supportedExtension(): string
            {
                return $this->extension;
            }

            public function parse(RawActivityFile $file): ParsedActivityFile
            {
                throw new \LogicException('parsed-by-'.$this->extension);
            }
        };
    }
}
