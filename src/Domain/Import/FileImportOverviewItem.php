<?php

declare(strict_types=1);

namespace App\Domain\Import;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ImportSource;
use App\Infrastructure\Repository\Item;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class FileImportOverviewItem implements Item
{
    private function __construct(
        private FileImportId $fileImportId,
        private string $originalFilename,
        private ImportSource $source,
        private FileImportStatus $status,
        private ?string $errorMessage,
        private ?ActivityId $activityId,
        private SerializableDateTime $importedOn,
    ) {
    }

    public static function fromState(
        FileImportId $fileImportId,
        string $originalFilename,
        ImportSource $source,
        FileImportStatus $status,
        ?string $errorMessage,
        ?ActivityId $activityId,
        SerializableDateTime $importedOn,
    ): self {
        return new self(
            fileImportId: $fileImportId,
            originalFilename: $originalFilename,
            source: $source,
            status: $status,
            errorMessage: $errorMessage,
            activityId: $activityId,
            importedOn: $importedOn,
        );
    }

    public function getId(): FileImportId
    {
        return $this->fileImportId;
    }

    public function getOriginalFilename(): string
    {
        return $this->originalFilename;
    }

    public function getSource(): ImportSource
    {
        return $this->source;
    }

    public function getStatus(): FileImportStatus
    {
        return $this->status;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function getActivityId(): ?ActivityId
    {
        return $this->activityId;
    }

    public function getImportedOn(): SerializableDateTime
    {
        return $this->importedOn;
    }
}
