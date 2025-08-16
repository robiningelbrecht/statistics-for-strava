<?php

declare(strict_types=1);

namespace App\Domain\Activity\Stream\CombinedStream;

use App\Domain\Activity\ActivityId;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
final readonly class CombinedActivityStream
{
    /**
     * @param array<mixed> $data
     */
    private function __construct(
        #[ORM\Id, ORM\Column(type: 'string')]
        private ActivityId $activityId,
        #[ORM\Id, ORM\Column(type: 'string')]
        private UnitSystem $unitSystem,
        #[ORM\Column(type: 'string')]
        private CombinedStreamTypes $streamTypes,
        #[ORM\Column(type: 'json')]
        private array $data,
    ) {
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
    public function getOtherStreamData(CombinedStreamType $streamType): array
    {
        $index = array_search($streamType, $this->streamTypes->toArray(), true);
        if (false === $index) {
            return [];
        }

        return array_column($this->data, $index);
    }
}
