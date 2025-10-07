<?php

declare(strict_types=1);

namespace App\Domain\Integration\AI\Tool;

use App\Domain\Activity\SportType\SportType;
use App\Domain\Athlete\AthleteRepository;
use App\Domain\Athlete\HeartRateZone\HeartRateZoneConfiguration;
use App\Domain\Athlete\MaxHeartRate\MaxHeartRateFormula;
use App\Infrastructure\Time\Clock\Clock;
use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolProperty;

final class GetDefaultHeartRateZone extends Tool
{
    public function __construct(
        private readonly AthleteRepository $athleteRepository,
        private readonly HeartRateZoneConfiguration $configuration,
        private readonly MaxHeartRateFormula $maxHeartRateFormula,
        private readonly Clock $clock,
    ) {
        parent::__construct(
            'get_heart_rate_zones',
            <<<DESC
            Retrieves the athlete’s personalized heart rate zones from the database.
            Use this tool when the user asks about their heart rate zones, training intensity, or zone-based performance
            Returns zone ranges based on the athlete’s max heart rate or custom configuration.
            DESC
        );
    }

    /**
     * @return \NeuronAI\Tools\ToolPropertyInterface[]
     *
     * @codeCoverageIgnore
     */
    #[\Override]
    protected function properties(): array
    {
        return [
            new ToolProperty(
                name: 'sportType',
                type: PropertyType::STRING,
                description: 'The sport type to get the heart rate zones for.',
                required: false
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function __invoke(?string $sportType): array
    {
        $now = $this->clock->getCurrentDateTimeImmutable();
        $sportType = SportType::tryFrom($sportType ?? '');
        $heartRateZones = $this->configuration->getDefaultHearRateZones($sportType);

        $athlete = $this->athleteRepository->find();

        $maxHeartRate = $this->maxHeartRateFormula->calculate(
            age: $athlete->getAgeInYears($now),
            on: $now,
        );

        return [
            'zone1' => $heartRateZones->getZoneOne()->getRangeInBpm($maxHeartRate),
            'zone2' => $heartRateZones->getZoneTwo()->getRangeInBpm($maxHeartRate),
            'zone3' => $heartRateZones->getZoneThree()->getRangeInBpm($maxHeartRate),
            'zone4' => $heartRateZones->getZoneFour()->getRangeInBpm($maxHeartRate),
            'zone5' => $heartRateZones->getZoneFive()->getRangeInBpm($maxHeartRate),
        ];
    }
}
