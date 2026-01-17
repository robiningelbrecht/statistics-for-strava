<?php

namespace App\Domain\Activity;

use App\Domain\Activity\SportType\SportTypes;
use App\Infrastructure\ValueObject\Time\Years;

interface ActivityIdRepository
{
    public function count(): int;

    public function findAll(): ActivityIds;

    public function hasForSportTypes(SportTypes $sportTypes): bool;

    public function findMarkedForDeletion(): ActivityIds;

    // @TODO: Move to QueryBus.
    public function findLongestFor(Years $years): ActivityId;
}
