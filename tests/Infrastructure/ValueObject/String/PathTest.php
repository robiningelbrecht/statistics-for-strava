<?php

namespace App\Tests\Infrastructure\ValueObject\String;

use App\Infrastructure\ValueObject\String\Path;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PathTest extends TestCase
{
    #[DataProvider('providePaths')]
    public function testItShouldParsePath(
        string $path,
        string $expectedFilename,
        string $expectedFilenameWithoutExtension,
        string $expectedExtension,
    ): void {
        self::assertEquals($expectedFilename, Path::fromString($path)->getFilename());
        self::assertEquals($expectedFilenameWithoutExtension, Path::fromString($path)->getFilenameWithoutExtension());
        self::assertEquals($expectedExtension, Path::fromString($path)->getExtension());
    }

    /**
     * @return iterable<string, array{string, string, string, string}>
     */
    public static function providePaths(): iterable
    {
        yield 'simple' => ['photo.jpg', 'photo.jpg', 'photo', 'jpg'];
        yield 'uppercase extension is lowercased' => ['PHOTO.JPG', 'PHOTO.jpg', 'PHOTO', 'jpg'];
        yield 'double extension' => ['/var/data/archive.tar.gz', 'archive.tar.gz', 'archive.tar', 'gz'];
        yield 'with directory' => ['activities/ride.FIT', 'ride.fit', 'ride', 'fit'];
        yield 'no extension' => ['/var/data/noextension', 'noextension', 'noextension', ''];
    }
}
