<?php

declare(strict_types=1);

namespace App\Domain\Athlete;

use App\Domain\Athlete\MaxHeartRate\MaxHeartRateFormula;
use App\Domain\Athlete\RestingHeartRate\RestingHeartRateFormula;
use App\Infrastructure\KeyValue\Key;
use App\Infrastructure\KeyValue\KeyValue;
use App\Infrastructure\KeyValue\KeyValueStore;
use App\Infrastructure\KeyValue\Value;
use App\Infrastructure\Serialization\Json;

final class KeyValueBasedAthleteRepository implements AthleteRepository
{
    private ?Athlete $cachedAthlete = null;

    public function __construct(
        private readonly KeyValueStore $keyValueStore,
        private readonly MaxHeartRateFormula $maxHeartRateFormula,
        private readonly RestingHeartRateFormula $restingHeartRateFormula,
    ) {
    }

    public function save(Athlete $athlete): void
    {
        $this->keyValueStore->save(KeyValue::fromState(
            key: Key::ATHLETE,
            value: Value::fromString(Json::encode($athlete))
        ));
        $this->cachedAthlete = null;
    }

    public function find(): Athlete
    {
        if ($this->cachedAthlete instanceof Athlete) {
            return $this->cachedAthlete;
        }

        $data = $this->keyValueStore->find(Key::ATHLETE);

        $athlete = Athlete::create(Json::decode((string) $data));
        $athlete
            ->setMaxHeartRateFormula($this->maxHeartRateFormula)
            ->setRestingHeartRateFormula($this->restingHeartRateFormula);
        $this->cachedAthlete = $athlete;

        return $this->cachedAthlete;
    }
}
