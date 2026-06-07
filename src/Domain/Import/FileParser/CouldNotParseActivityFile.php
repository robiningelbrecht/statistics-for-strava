<?php

declare(strict_types=1);

namespace App\Domain\Import\FileParser;

final class CouldNotParseActivityFile extends \RuntimeException
{
    public function __construct(
        string $message,
        private readonly RawActivityFile $activityFile,
    ) {
        parent::__construct($message);
    }

    public function getActivityFile(): RawActivityFile
    {
        return $this->activityFile;
    }
}
