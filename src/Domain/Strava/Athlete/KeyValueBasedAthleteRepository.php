<?php

declare(strict_types=1);

namespace App\Domain\Strava\Athlete;

use App\Domain\Strava\Athlete\MaxHeartRate\MaxHeartRateFormula;
use App\Infrastructure\KeyValue\Key;
use App\Infrastructure\KeyValue\KeyValue;
use App\Infrastructure\KeyValue\KeyValueStore;
use App\Infrastructure\KeyValue\Value;
use App\Infrastructure\Serialization\Json;

final readonly class KeyValueBasedAthleteRepository implements AthleteRepository
{
    public function __construct(
        private KeyValueStore $keyValueStore,
        private MaxHeartRateFormula $maxHeartRateFormula,
    ) {
    }

    public function save(Athlete $athlete): void
    {
        $this->keyValueStore->save(KeyValue::fromState(
            key: Key::ATHLETE,
            value: Value::fromString(Json::encode($athlete))
        ));
    }

    public function find(): Athlete
    {
        $data = $this->keyValueStore->find(Key::ATHLETE);

        $athlete = Athlete::create(Json::decode((string) $data));
        $athlete->setMaxHeartRateFormula($this->maxHeartRateFormula);

        return $athlete;
    }
}
