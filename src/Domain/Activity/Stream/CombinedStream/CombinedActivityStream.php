<?php

declare(strict_types=1);

namespace App\Domain\Activity\Stream\CombinedStream;

use App\Domain\Activity\ActivityId;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
final class CombinedActivityStream
{
    /** @var array<string, array<int, float>> */
    private array $chartStreamDataCache;
    private readonly CombinedStreamTypes $streamTypesForCharts;

    /**
     * @param array<mixed> $data
     */
    private function __construct(
        #[ORM\Id, ORM\Column(type: 'string')]
        private readonly ActivityId $activityId,
        #[ORM\Id, ORM\Column(type: 'string')]
        private readonly UnitSystem $unitSystem,
        #[ORM\Column(type: 'string')]
        private readonly CombinedStreamTypes $streamTypes,
        #[ORM\Column(type: 'json')]
        private readonly array $data,
    ) {
        $this->streamTypesForCharts = CombinedStreamTypes::empty();
        $streamTypes = $this->streamTypes->toArray();

        foreach ($this->streamTypes as $streamType) {
            if (in_array($streamType, [CombinedStreamType::DISTANCE, CombinedStreamType::LAT_LNG, CombinedStreamType::TIME])) {
                continue;
            }

            $index = array_search($streamType, $streamTypes, true);
            if (false === $index) {
                continue;
            }

            $this->streamTypesForCharts->add($streamType);
            $this->chartStreamDataCache[$streamType->value] = array_column($this->data, $index);
        }
    }

    /**
     * @param array<mixed> $data
     */
    public static function create(
        ActivityId $activityId,
        UnitSystem $unitSystem,
        CombinedStreamTypes $streamTypes,
        array $data,
    ): self {
        return new self(
            activityId: $activityId,
            unitSystem: $unitSystem,
            streamTypes: $streamTypes,
            data: $data,
        );
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromState(
        ActivityId $activityId,
        UnitSystem $unitSystem,
        CombinedStreamTypes $streamTypes,
        array $data,
    ): self {
        return new self(
            activityId: $activityId,
            unitSystem: $unitSystem,
            streamTypes: $streamTypes,
            data: $data,
        );
    }

    public function getActivityId(): ActivityId
    {
        return $this->activityId;
    }

    public function getUnitSystem(): UnitSystem
    {
        return $this->unitSystem;
    }

    public function getStreamTypes(): CombinedStreamTypes
    {
        return $this->streamTypes;
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return array<int, float>
     */
    public function getDistances(): array
    {
        $distanceIndex = array_search(CombinedStreamType::DISTANCE, $this->streamTypes->toArray(), true);
        if (false === $distanceIndex) {
            return [];
        }

        return array_column($this->data, $distanceIndex);
    }

    /**
     * @return array<int, float>
     */
    public function getTimes(): array
    {
        $distanceIndex = array_search(CombinedStreamType::TIME, $this->streamTypes->toArray(), true);
        if (false === $distanceIndex) {
            return [];
        }

        return array_column($this->data, $distanceIndex);
    }

    /**
     * @return array<int, array<float, float>>
     */
    public function getCoordinates(): array
    {
        $coordinateIndex = array_search(CombinedStreamType::LAT_LNG, $this->streamTypes->toArray(), true);
        if (false === $coordinateIndex) {
            return [];
        }

        return array_column($this->data, $coordinateIndex);
    }

    public function getStreamTypesForCharts(): CombinedStreamTypes
    {
        return $this->streamTypesForCharts;
    }

    /**
     * @return array<int, float>
     */
    public function getChartStreamData(CombinedStreamType $streamType): array
    {
        return $this->chartStreamDataCache[$streamType->value] ?? [];
    }
}
