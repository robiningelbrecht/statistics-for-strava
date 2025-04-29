<?php

namespace App\Domain\Strava\Activity\Stream;

use App\Domain\Strava\Activity\ActivityId;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'ActivityStream')]
#[ORM\Index(name: 'ActivityStream_activityIndex', columns: ['activityId'])]
#[ORM\Index(name: 'ActivityStream_streamTypeIndex', columns: ['streamType'])]
final class ActivityStream
{
    /**
     * @param array<mixed>    $data
     * @param array<int, int> $bestAverages
     */
    private function __construct(
        #[ORM\Id, ORM\Column(type: 'string')]
        private readonly ActivityId $activityId,
        #[ORM\Id, ORM\Column(type: 'string')]
        private readonly StreamType $streamType,
        #[ORM\Column(type: 'datetime_immutable')]
        private readonly SerializableDateTime $createdOn,
        #[ORM\Column(type: 'json')]
        private readonly array $data,
        #[ORM\Column(type: 'json', nullable: true)]
        private array $bestAverages = [],
    ) {
    }

    /**
     * @param array<mixed> $streamData
     */
    public static function create(
        ActivityId $activityId,
        StreamType $streamType,
        array $streamData,
        SerializableDateTime $createdOn,
    ): self {
        return new self(
            activityId: $activityId,
            streamType: $streamType,
            createdOn: $createdOn,
            data: $streamData,
        );
    }

    /**
     * @param array<mixed>    $streamData
     * @param array<int, int> $bestAverages
     */
    public static function fromState(
        ActivityId $activityId,
        StreamType $streamType,
        array $streamData,
        SerializableDateTime $createdOn,
        array $bestAverages,
    ): self {
        return new self(
            activityId: $activityId,
            streamType: $streamType,
            createdOn: $createdOn,
            data: $streamData,
            bestAverages: $bestAverages
        );
    }

    public function getName(): string
    {
        return $this->getActivityId()->toUnprefixedString().' - '.$this->getStreamType()->value;
    }

    public function getCreatedOn(): SerializableDateTime
    {
        return $this->createdOn;
    }

    public function getActivityId(): ActivityId
    {
        return $this->activityId;
    }

    public function getStreamType(): StreamType
    {
        return $this->streamType;
    }

    /**
     * @return array<mixed>
     */
    public function getData(): array
    {
        if (StreamType::HEART_RATE === $this->getStreamType() && !empty($this->data) && max($this->data) > 300) {
            // Max BPM of 300, WTF? Must be faulty data.
            return [];
        }

        return $this->data;
    }

    /**
     * @return array<int, int>
     */
    public function getBestAverages(): array
    {
        return $this->bestAverages;
    }

    /**
     * @param array<int, int> $averages
     */
    public function updateBestAverages(array $averages): void
    {
        $this->bestAverages = $averages;
    }

    public function calculateBestAverageForTimeInterval(int $timeIntervalInSeconds): ?int
    {
        $data = $this->getData();
        $n = count($data);
        if ($n < $timeIntervalInSeconds) {
            // Not enough data.
            return null;
        }
        // Compute initial sum for the first X seconds.
        $currentSum = array_sum(array_slice($data, 0, $timeIntervalInSeconds));
        $maxSum = $currentSum;

        // Sliding window approach.
        for ($i = $timeIntervalInSeconds; $i < $n; ++$i) {
            $currentSum += $data[$i] - $data[$i - $timeIntervalInSeconds];
            $maxSum = max($maxSum, $currentSum);
        }

        return (int) round($maxSum / $timeIntervalInSeconds);
    }
}
