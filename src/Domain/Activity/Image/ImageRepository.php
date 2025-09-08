<?php

namespace App\Domain\Activity\Image;

use App\Domain\Activity\SportType\SportTypes;
use App\Infrastructure\ValueObject\Time\Years;

interface ImageRepository
{
    public function findBySportTypes(SportTypes $sportTypes): Images;

    public function countBySportTypes(SportTypes $sportTypes): int;

    public function findRandomFor(SportTypes $sportTypes, Years $years): Image;
}
