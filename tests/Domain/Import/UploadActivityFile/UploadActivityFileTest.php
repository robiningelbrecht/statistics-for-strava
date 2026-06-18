<?php

declare(strict_types=1);

namespace App\Tests\Domain\Import\UploadActivityFile;

use App\Domain\Import\UploadActivityFile\UploadActivityFile;
use App\Infrastructure\CQRS\Command\Deserialize\CouldNotDeserializeCommand;
use PHPUnit\Framework\TestCase;

class UploadActivityFileTest extends TestCase
{
    public function testFromPayload(): void
    {
        $command = UploadActivityFile::fromPayload([
            'filename' => 'ride.fit',
            'content' => base64_encode('raw-fit-bytes'),
        ]);

        $this->assertSame('ride.fit', $command->getFilename());
        $this->assertSame('raw-fit-bytes', $command->getContents());
    }

    public function testFromPayloadStripsPathTraversal(): void
    {
        $command = UploadActivityFile::fromPayload([
            'filename' => '../../etc/foo.gpx',
            'content' => base64_encode('raw-gpx-bytes'),
        ]);

        $this->assertSame('foo.gpx', $command->getFilename());
    }

    public function testFromPayloadThrowsOnMissingFilename(): void
    {
        $this->expectException(CouldNotDeserializeCommand::class);

        UploadActivityFile::fromPayload([
            'content' => base64_encode('raw-fit-bytes'),
        ]);
    }

    public function testFromPayloadThrowsOnMissingContent(): void
    {
        $this->expectException(CouldNotDeserializeCommand::class);

        UploadActivityFile::fromPayload([
            'filename' => 'ride.fit',
        ]);
    }

    public function testFromPayloadThrowsOnUnsupportedExtension(): void
    {
        $this->expectException(CouldNotDeserializeCommand::class);

        UploadActivityFile::fromPayload([
            'filename' => 'notes.txt',
            'content' => base64_encode('some text'),
        ]);
    }

    public function testFromPayloadThrowsOnMalformedContent(): void
    {
        $this->expectException(CouldNotDeserializeCommand::class);

        UploadActivityFile::fromPayload([
            'filename' => 'ride.fit',
            'content' => 'not-valid-base64!!!',
        ]);
    }

    public function testFromPayloadThrowsOnEmptyContent(): void
    {
        $this->expectException(CouldNotDeserializeCommand::class);

        UploadActivityFile::fromPayload([
            'filename' => 'ride.fit',
            'content' => '',
        ]);
    }
}
