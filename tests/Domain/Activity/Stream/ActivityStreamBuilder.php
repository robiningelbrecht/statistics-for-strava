<?php

declare(strict_types=1);

namespace App\Tests\Domain\Activity\Stream;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\Stream\ActivityStream;
use App\Domain\Activity\Stream\StreamType;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final class ActivityStreamBuilder
{
    private ActivityId $activityId;
    private StreamType $streamType;
    private SerializableDateTime $createdOn;
    private array $data;
    private array $bestAverages;
    private array $valueDistribution;
    private ?int $normalizedPower;

    private function __construct()
    {
        $this->activityId = ActivityId::fromUnprefixed('1234');
        $this->streamType = StreamType::WATTS;
        $this->createdOn = SerializableDateTime::fromString('2023-10-10');
        $this->data = [];
        $this->bestAverages = [];
        $this->valueDistribution = [];
        $this->normalizedPower = null;
    }

    public static function fromDefaults(): self
    {
        return new self();
    }

    public function build(): ActivityStream
    {
        return ActivityStream::fromState(
            activityId: $this->activityId,
            streamType: $this->streamType,
            streamData: $this->data,
            createdOn: $this->createdOn,
            valueDistribution: $this->valueDistribution,
            bestAverages: $this->bestAverages,
            normalizedPower: $this->normalizedPower,
        );
    }

    public function withActivityId(ActivityId $activityId): self
    {
        $this->activityId = $activityId;

        return $this;
    }

    public function withStreamType(StreamType $streamType): self
    {
        $this->streamType = $streamType;

        return $this;
    }

    public function withCreatedOn(SerializableDateTime $createdOn): self
    {
        $this->createdOn = $createdOn;

        return $this;
    }

    public function withData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function withBestAverages(array $bestAverages): self
    {
        $this->bestAverages = $bestAverages;

        return $this;
    }

    public function withNormalizedPower(int $normalizedPower): self
    {
        $this->normalizedPower = $normalizedPower;

        return $this;
    }

    public function withValueDistribution(array $valueDistribution): self
    {
        $this->valueDistribution = $valueDistribution;

        return $this;
    }
}
