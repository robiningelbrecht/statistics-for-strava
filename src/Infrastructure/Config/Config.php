<?php

declare(strict_types=1);

namespace App\Infrastructure\Config;

use App\Domain\Gear\Gear;
use App\Domain\Gear\GearId;
use App\Domain\Gear\GearIds;
use App\Domain\Gear\GearRepository;
use App\Domain\Gear\Maintenance\GearMaintenanceConfig;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\KeyValue\Key;
use App\Infrastructure\KeyValue\KeyValueStore;
use App\Infrastructure\Serialization\Json;

final readonly class Config
{
    public function __construct(
        private KeyValueStore $keyValueStore,
        private GearRepository $gearRepository,
    ) {
    }

    public function loadGearMaintenance(): GearMaintenanceConfig
    {
        try {
            /** @var array<string, mixed>|null $config */
            $config = Json::decode((string) $this->keyValueStore->find(Key::GEAR_MAINTENANCE));
        } catch (EntityNotFound) {
            // No record: gear maintenance has not been configured.
            $config = null;
        }

        $gearMaintenanceConfig = GearMaintenanceConfig::fromArray(is_array($config) ? $config : null);

        // The config references gear ids that may be unprefixed (copy-pasted from a gear URL),
        // while the database stores them with their Strava "b"/"g" prefix. Normalize them here
        // so every consumer works with ids that match the database.
        $gearMaintenanceConfig->normalizeGearIds(GearIds::fromArray(
            $this->gearRepository->findAll()->map(fn (Gear $gear): GearId => $gear->getId())
        ));

        return $gearMaintenanceConfig;
    }
}
