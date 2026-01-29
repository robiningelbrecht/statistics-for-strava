<?php

declare(strict_types=1);

namespace App\Domain\Activity\BestEffort;

use App\Domain\Activity\SportType\SportType;
use App\Domain\Activity\SportType\SportTypes;
use App\Infrastructure\ValueObject\Collection;
use App\Infrastructure\ValueObject\Measurement\Length\ConvertableToMeter;

/**
 * @extends Collection<ActivityBestEffort>
 */
final class ActivityBestEfforts extends Collection
{
    private SportTypes $sportTypes;
    /** @var array<string, ActivityBestEffort> */
    private array $bestEfforts;
    /** @var array<string, string[]> */
    private array $bestEffortsPerSportType;
    /** @var array<string, array<int, string[]>> */
    private array $bestEffortsPerSportTypeAndDistance;

    public function getItemClassName(): string
    {
        return ActivityBestEffort::class;
    }

    #[\Override]
    public function add(mixed $item): self
    {
        if (!isset($this->sportTypes)) {
            $this->sportTypes = SportTypes::empty();
        }

        $sportType = $item->getSportType();
        if (!$this->sportTypes->has($sportType)) {
            $this->sportTypes->add($sportType);
        }

        $id = $item->getId();
        $this->bestEfforts[$id] = $item;

        $this->bestEffortsPerSportType[$sportType->value][] = $id;
        $this->bestEffortsPerSportTypeAndDistance[$sportType->value][$item->getDistanceInMeter()->toInt()][] = $id;

        /** @var ActivityBestEfforts $collection */
        $collection = parent::add($item);

        return $collection;
    }

    public function getSportTypes(): SportTypes
    {
        return $this->sportTypes;
    }

    public function getBySportType(SportType $sportType): ActivityBestEfforts
    {
        $ids = $this->bestEffortsPerSportType[$sportType->value] ?? [];

        return ActivityBestEfforts::fromArray($this->resolveIds($ids));
    }

    public function getBySportTypeAndDistance(SportType $sportType, ConvertableToMeter $distance): ActivityBestEfforts
    {
        $distance = $distance->toMeter()->toInt();
        $ids = $this->bestEffortsPerSportTypeAndDistance[$sportType->value][$distance] ?? [];

        return ActivityBestEfforts::fromArray($this->resolveIds($ids));
    }

    /**
     * @param string[] $ids
     *
     * @return ActivityBestEffort[]
     */
    private function resolveIds(array $ids): array
    {
        return array_map(fn (string $id) => $this->bestEfforts[$id], $ids);
    }
}
