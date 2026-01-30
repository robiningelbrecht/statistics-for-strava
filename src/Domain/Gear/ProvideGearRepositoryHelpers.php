<?php

declare(strict_types=1);

namespace App\Domain\Gear;

use App\Domain\Activity\SportType\SportType;
use App\Domain\Activity\SportType\SportTypes;
use App\Domain\Gear\CustomGear\CustomGear;
use App\Domain\Gear\ImportedGear\ImportedGear;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\DBAL\Connection;

trait ProvideGearRepositoryHelpers
{
    abstract protected function getConnection(): Connection;

    public function save(Gear $gear, GearType $gearType): void
    {
        $sql = 'REPLACE INTO Gear (gearId, createdOn, distanceInMeter, name, isRetired, `type`)
        VALUES (:gearId, :createdOn, :distanceInMeter, :name, :isRetired, :type)';

        $this->getConnection()->executeStatement($sql, [
            'gearId' => $gear->getId(),
            'createdOn' => $gear->getCreatedOn(),
            'distanceInMeter' => $gear->getDistance()->toMeter()->toInt(),
            'name' => $gear->getOriginalName(),
            'isRetired' => (int) $gear->isRetired(),
            'type' => $gearType->value,
        ]);
    }

    public function findAll(GearType $gearType): Gears
    {
        return $this->fetchFindAllResults(
            gearType: $gearType,
            onlyUsedGear: false
        );
    }

    public function findAllUsed(GearType $gearType): Gears
    {
        return $this->fetchFindAllResults(
            gearType: $gearType,
            onlyUsedGear: true
        );
    }

    private function fetchFindAllResults(GearType $gearType, bool $onlyUsedGear): Gears
    {
        $results = $this->getConnection()->executeQuery('
            SELECT Gear.*, GROUP_CONCAT(DISTINCT Activity.sportType) AS sportTypes
            FROM Gear
            '.($onlyUsedGear ? 'INNER' : 'LEFT').' JOIN Activity ON Activity.gearId = Gear.gearId
            WHERE Gear.type = :type
            GROUP BY Gear.gearId, Gear.isRetired, Gear.distanceInMeter
            ORDER BY Gear.isRetired ASC, Gear.distanceInMeter DESC;
        ',
            [
                'type' => $gearType->value,
            ]
        )->fetchAllAssociative();

        return Gears::fromArray(array_map(
            fn (array $result) => $this->hydrate($result),
            $results
        ));
    }

    /**
     * @param array<string, mixed> $result
     */
    private function hydrate(array $result): ImportedGear|CustomGear
    {
        $gearType = GearType::from($result['type']);
        $args = [
            'gearId' => GearId::fromString($result['gearId']),
            'type' => $gearType,
            'distanceInMeter' => Meter::from($result['distanceInMeter']),
            'createdOn' => SerializableDateTime::fromString($result['createdOn']),
            'name' => $result['name'],
            'isRetired' => (bool) $result['isRetired'],
        ];

        $gear = match ($gearType) {
            GearType::IMPORTED => ImportedGear::fromState(...$args),
            GearType::CUSTOM => CustomGear::fromState(...$args),
        };

        $sportTypes = SportTypes::empty();
        if (!empty($result['sportTypes'])) {
            $sportTypes = SportTypes::fromArray(array_map(
                SportType::from(...),
                explode(',', (string) $result['sportTypes'])
            ));
        }

        return $gear->withSportTypes($sportTypes);
    }
}
