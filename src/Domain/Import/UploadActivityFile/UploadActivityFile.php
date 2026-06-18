<?php

declare(strict_types=1);

namespace App\Domain\Import\UploadActivityFile;

use App\Domain\Import\SupportedFileExtension;
use App\Infrastructure\CQRS\Command\Deserialize\AsDeserializableCommand;
use App\Infrastructure\CQRS\Command\Deserialize\CouldNotDeserializeCommand;
use App\Infrastructure\CQRS\Command\Deserialize\DeserializableCommand;
use App\Infrastructure\CQRS\Command\DomainCommand;
use App\Infrastructure\ValueObject\String\Path;

#[AsDeserializableCommand('upload-activity-file')]
final readonly class UploadActivityFile extends DomainCommand implements DeserializableCommand
{
    private function __construct(
        private string $filename,
        private string $contents,
    ) {
    }

    public static function fromPayload(array $payload): self
    {
        if (!isset($payload['filename'], $payload['content'])
            || !is_string($payload['filename'])
            || !is_string($payload['content'])) {
            throw CouldNotDeserializeCommand::invalidPayload();
        }

        $filename = basename($payload['filename']);

        if (!SupportedFileExtension::tryFrom(Path::fromString($filename)->getExtension())) {
            throw CouldNotDeserializeCommand::invalidPayload();
        }

        $contents = base64_decode($payload['content'], strict: true);
        if (false === $contents || '' === $contents) {
            throw CouldNotDeserializeCommand::invalidPayload();
        }

        return new self(
            filename: $filename,
            contents: $contents,
        );
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getContents(): string
    {
        return $this->contents;
    }
}
