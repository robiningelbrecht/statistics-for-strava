<?php

declare(strict_types=1);

namespace App\Domain\Import\UploadActivityFile;

final class CannotUploadActivityFile extends \RuntimeException
{
    public static function importModeIsNotFiles(): self
    {
        return new self('Activity files can only be uploaded when running in file import mode.');
    }
}
