<?php

declare(strict_types=1);

namespace App\Domain\Strava;

use App\Domain\Strava\Activity\ActivityRepository;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\KeyValue\Key;
use App\Infrastructure\KeyValue\KeyValue;
use App\Infrastructure\KeyValue\KeyValueStore;
use App\Infrastructure\KeyValue\Value;
use App\Infrastructure\Time\Clock\Clock;

final readonly class StravaDataImportStatus
{
    public function __construct(
        private ActivityRepository $activityRepository,
        private KeyValueStore $keyValueStore,
        private Clock $clock,
    ) {
    }

    public function markGearImportAsCompleted(): void
    {
        $this->keyValueStore->save(KeyValue::fromState(
            key: Key::STRAVA_GEAR_IMPORT,
            value: Value::fromString($this->clock->getCurrentDateTimeImmutable()->format('Y-m-d')),
        ));
    }

    public function markGearImportAsUncompleted(): void
    {
        $this->keyValueStore->clear(Key::STRAVA_GEAR_IMPORT);
    }

    public function gearImportIsCompleted(): bool
    {
        try {
            $this->keyValueStore->find(Key::STRAVA_GEAR_IMPORT);
        } catch (EntityNotFound) {
            return false;
        }

        return true;
    }

    public function isCompleted(): bool
    {
        return $this->gearImportIsCompleted() && $this->activityRepository->count() > 0;
    }
}
