<?php

namespace App\Domain\Strava\Activity\Image;

use App\Domain\Strava\Activity\SportType\SportTypes;
use App\Infrastructure\ValueObject\Time\Year;

interface ImageRepository
{
    public function findAll(): Images;

    public function count(): int;

    public function findRandomFor(SportTypes $sportTypes, Year $year): Image;
}
