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
    private StreamType $streamType = StreamType::WATTS;
    private SerializableDateTime $createdOn;
    private array $data = [];
    private array $computedFieldsState = [];
    private array $bestAverages = [];
    private array $valueDistribution = [];
    private ?int $normalizedPower = null;

    private function __construct()
    {
        $this->activityId = ActivityId::fromUnprefixed('1234');
        $this->createdOn = SerializableDateTime::fromString('2023-10-10');
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
            computedFieldsState: $this->computedFieldsState,
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
        $this->computedFieldsState[ActivityStream::COMPUTED_FIELD_BEST_AVERAGES] = true;

        return $this;
    }

    public function withNormalizedPower(int $normalizedPower): self
    {
        $this->normalizedPower = $normalizedPower;
        $this->computedFieldsState[ActivityStream::COMPUTED_FIELD_NORMALIZED_POWER] = true;

        return $this;
    }

    public function withValueDistribution(array $valueDistribution): self
    {
        $this->valueDistribution = $valueDistribution;
        $this->computedFieldsState[ActivityStream::COMPUTED_FIELD_VALUE_DISTRIBUTION] = true;

        return $this;
    }
}
