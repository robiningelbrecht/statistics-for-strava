<?php

declare(strict_types=1);

namespace App\BuildApp\ConfigureAppColors;

use App\Domain\Activity\ActivityTypeRepository;
use App\Domain\Activity\SportType\SportTypeRepository;
use App\Domain\Gear\GearRepository;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use App\Infrastructure\KeyValue\Key;
use App\Infrastructure\KeyValue\KeyValue;
use App\Infrastructure\KeyValue\KeyValueStore;
use App\Infrastructure\KeyValue\Value;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\Theme\Theme;

final readonly class ConfigureAppColorsCommandHandler implements CommandHandler
{
    public function __construct(
        private SportTypeRepository $sportTypeRepository,
        private ActivityTypeRepository $activityTypeRepository,
        private GearRepository $gearRepository,
        private KeyValueStore $keyValueStore,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof ConfigureAppColors);

        $configuredColors = [];
        $defaultChatColors = Theme::defaultChartColors();

        $sportTypes = $this->sportTypeRepository->findAll();
        foreach ($sportTypes as $index => $sportType) {
            $configuredColors['sportType'][$sportType->value] = $defaultChatColors[$index % count($defaultChatColors)];
        }

        $activityTypes = $this->activityTypeRepository->findAll();
        foreach ($activityTypes as $index => $activityType) {
            $configuredColors['activityType'][$activityType->value] = $defaultChatColors[$index % count($defaultChatColors)];
        }

        $gears = $this->gearRepository->findAll();
        foreach ($gears as $index => $gear) {
            $configuredColors['gear'][(string) $gear->getId()] = $defaultChatColors[$index % count($defaultChatColors)];
        }

        $this->keyValueStore->save(KeyValue::fromState(
            Key::THEME,
            Value::fromString(Json::encode($configuredColors)),
        ));
    }
}
