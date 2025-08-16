<?php

namespace App\Domain\Activity\Image;

use App\Domain\Activity\SportType\SportTypes;
use App\Infrastructure\ValueObject\Time\Years;

interface ImageRepository
{
    public function findAll(): Images;

    public function count(): int;

    public function findRandomFor(SportTypes $sportTypes, Years $years): Image;
}
