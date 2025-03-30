<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Stream\CombinedStream;

use App\Domain\Strava\Activity\ActivityId;
use App\Domain\Strava\Activity\Stream\StreamType;
use App\Domain\Strava\Activity\Stream\StreamTypes;
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
        private StreamTypes $streamTypes,
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
        StreamTypes $streamTypes,
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
        StreamTypes $streamTypes,
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

    public function getStreamTypes(): StreamTypes
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

    public function getDistances(): array
    {
        $distanceIndex = array_search(StreamType::DISTANCE, $this->streamTypes->toArray(), true);

        return array_column($this->data, $distanceIndex);
    }

    public function getAltitudes(): array
    {
        $altitudeIndex = array_search(StreamType::ALTITUDE, $this->streamTypes->toArray(), true);

        return array_column($this->data, $altitudeIndex);
    }
}
