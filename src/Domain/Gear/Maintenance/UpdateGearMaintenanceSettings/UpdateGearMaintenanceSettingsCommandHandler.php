<?php

declare(strict_types=1);

namespace App\Domain\Gear\Maintenance\UpdateGearMaintenanceSettings;

use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\KeyValue\Key;
use App\Infrastructure\KeyValue\KeyValue;
use App\Infrastructure\KeyValue\KeyValueStore;
use App\Infrastructure\KeyValue\Value;
use App\Infrastructure\Serialization\Json;

final readonly class UpdateGearMaintenanceSettingsCommandHandler implements CommandHandler
{
    public function __construct(
        private KeyValueStore $keyValueStore,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof UpdateGearMaintenanceSettings);

        try {
            $config = Json::decode((string) $this->keyValueStore->find(Key::GEAR_MAINTENANCE));
        } catch (EntityNotFound) {
            $config = [];
        }
        if (!is_array($config)) {
            $config = [];
        }

        $config['enabled'] = $command->isFeatureEnabled();
        $config['ignoreRetiredGear'] = $command->ignoreRetiredGear();

        $this->keyValueStore->save(KeyValue::fromState(
            Key::GEAR_MAINTENANCE,
            Value::fromString(Json::encode($config)),
        ));
    }
}
