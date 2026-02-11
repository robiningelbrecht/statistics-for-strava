<?php

namespace App\Domain\Activity;

use App\Domain\Activity\SportType\SportTypes;

interface ActivityIdRepository
{
    public function count(): int;

    public function findAll(): ActivityIds;

    public function findAllWithoutStravaGear(): ActivityIds;

    public function hasForSportTypes(SportTypes $sportTypes): bool;

    public function findMarkedForDeletion(): ActivityIds;
}
