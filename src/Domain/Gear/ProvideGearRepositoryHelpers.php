<?php

declare(strict_types=1);

namespace App\Domain\Gear;

use App\Domain\Activity\ActivityType;
use App\Domain\Activity\ActivityTypes;
use App\Domain\Gear\CustomGear\CustomGear;
use App\Domain\Gear\ImportedGear\ImportedGear;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\Time\Seconds;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\DBAL\Connection;
use Money\Currency;
use Money\Money;

trait ProvideGearRepositoryHelpers
{
    abstract protected function getConnection(): Connection;

    public function save(Gear $gear, GearType $gearType): void
    {
        $sql = 'REPLACE INTO Gear (gearId, createdOn, name, isRetired, `type`, purchasePriceAmount, purchasePriceCurrency)
        VALUES (:gearId, :createdOn, :name, :isRetired, :type, :purchasePriceAmount, :purchasePriceCurrency)';

        $purchasePrice = $gear->getPurchasePrice();
        $this->getConnection()->executeStatement($sql, [
            'gearId' => $gear->getId(),
            'createdOn' => $gear->getCreatedOn(),
            'name' => $gear->getOriginalName(),
            'isRetired' => (int) $gear->isRetired(),
            'type' => $gearType->value,
            'purchasePriceAmount' => $purchasePrice?->getAmount(),
            'purchasePriceCurrency' => $purchasePrice?->getCurrency()->getCode(),
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
            SELECT Gear.*,
                   SUM(Activity.distance) as totalDistance,
                   SUM(Activity.movingTimeInSeconds) as totalMovingTime,
                   SUM(Activity.elevation) as totalElevation,
                   SUM(Activity.calories) as totalCalories,
                   COUNT(Activity.activityId) as numberOfActivities,
                   GROUP_CONCAT(DISTINCT Activity.activityType) AS activityTypes
            FROM Gear
            '.($onlyUsedGear ? 'INNER' : 'LEFT').' JOIN Activity ON Activity.gearId = Gear.gearId
            WHERE Gear.type = :type
            GROUP BY Gear.gearId, Gear.isRetired
            ORDER BY Gear.isRetired ASC, totalDistance DESC;
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

        $purchasePrice = null;
        $purchasePriceCurrency = (string) ($result['purchasePriceCurrency'] ?? '');
        if (isset($result['purchasePriceAmount']) && '' !== $purchasePriceCurrency) {
            $purchasePrice = new Money(
                amount: (int) $result['purchasePriceAmount'],
                currency: new Currency($purchasePriceCurrency)
            );
        }

        $args = [
            'gearId' => GearId::fromString($result['gearId']),
            'distanceInMeter' => Meter::from((float) $result['totalDistance']),
            'createdOn' => SerializableDateTime::fromString($result['createdOn']),
            'name' => $result['name'],
            'isRetired' => (bool) $result['isRetired'],
            'movingTime' => Seconds::from((float) ($result['totalMovingTime'] ?? 0)),
            'elevation' => Meter::from((float) ($result['totalElevation'] ?? 0)),
            'numberOfActivities' => (int) ($result['numberOfActivities'] ?? 0),
            'totalCalories' => (int) ($result['totalCalories'] ?? 0),
            'purchasePrice' => $purchasePrice,
        ];

        $gear = match ($gearType) {
            GearType::IMPORTED => ImportedGear::fromState(...$args),
            GearType::CUSTOM => CustomGear::fromState(...$args),
        };

        $activityTypes = ActivityTypes::empty();
        if (!empty($result['activityTypes'])) {
            $activityTypes = ActivityTypes::fromArray(array_map(
                ActivityType::from(...),
                explode(',', (string) $result['activityTypes'])
            ));
        }

        return $gear->withActivityTypes($activityTypes);
    }
}
