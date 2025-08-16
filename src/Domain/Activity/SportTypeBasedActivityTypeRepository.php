<?php

declare(strict_types=1);

namespace App\Domain\Activity;

use App\Domain\Activity\SportType\SportType;
use App\Domain\Activity\SportType\SportTypeRepository;

final readonly class SportTypeBasedActivityTypeRepository implements ActivityTypeRepository
{
    public function __construct(
        private SportTypeRepository $sportTypeRepository,
    ) {
    }

    public function findAll(): ActivityTypes
    {
        $activityTypes = [];
        /** @var SportType $sportType */
        foreach ($this->sportTypeRepository->findAll() as $sportType) {
            $activityTypes[$sportType->getActivityType()->value] = $sportType->getActivityType();
        }

        return ActivityTypes::fromArray(array_values($activityTypes));
    }
}
