<?php

namespace App\Domain\Activity\Stream;

use App\Domain\Activity\ActivityId;
use App\Domain\Integration\AI\SupportsAITooling;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'ActivityStream')]
#[ORM\Index(name: 'ActivityStream_activityIndex', columns: ['activityId'])]
#[ORM\Index(name: 'ActivityStream_streamTypeIndex', columns: ['streamType'])]
final class ActivityStream implements SupportsAITooling
{
    /**
     * @param array<int, mixed> $data
     * @param array<int, int>   $valueDistribution
     * @param array<int, int>   $bestAverages
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
        #[ORM\Column(type: 'integer', nullable: true)]
        private ?int $normalizedPower,
        #[ORM\Column(type: 'json', nullable: true)]
        private array $valueDistribution,
        #[ORM\Column(type: 'json', nullable: true)]
        private array $bestAverages = [],
    ) {
    }

    /**
     * @param array<int, mixed> $streamData
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
            normalizedPower: null,
            valueDistribution: []
        );
    }

    /**
     * @param array<int, mixed> $streamData
     * @param array<int, int>   $bestAverages
     * @param array<int, int>   $valueDistribution
     */
    public static function fromState(
        ActivityId $activityId,
        StreamType $streamType,
        array $streamData,
        SerializableDateTime $createdOn,
        array $valueDistribution,
        array $bestAverages,
        ?int $normalizedPower,
    ): self {
        return new self(
            activityId: $activityId,
            streamType: $streamType,
            createdOn: $createdOn,
            data: $streamData,
            normalizedPower: $normalizedPower,
            valueDistribution: $valueDistribution,
            bestAverages: $bestAverages,
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

    public function applySimpleMovingAverage(int $windowSize): self
    {
        $count = count($this->data);
        if (0 === $count || $windowSize < 1) {
            return $this;
        }
        $half = intdiv($windowSize, 2);
        $smoothed = [];
        for ($i = 0; $i < $count; ++$i) {
            $start = max(0, $i - $half);
            $end = min($count - 1, $i + $half);
            $sum = 0.0;
            for ($j = $start; $j <= $end; ++$j) {
                $sum += $this->data[$j];
            }
            $smoothed[] = $sum / ($end - $start + 1);
        }

        return ActivityStream::fromState(
            activityId: $this->getActivityId(),
            streamType: $this->getStreamType(),
            streamData: $smoothed,
            createdOn: $this->getCreatedOn(),
            valueDistribution: $this->getValueDistribution(),
            bestAverages: $this->getBestAverages(),
            normalizedPower: $this->getNormalizedPower(),
        );
    }

    /**
     * @return array<int, mixed>
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
    public function getValueDistribution(): array
    {
        return $this->valueDistribution;
    }

    /**
     * @param array<int, int> $valueDistribution
     */
    public function updateValueDistribution(array $valueDistribution): void
    {
        $this->valueDistribution = $valueDistribution;
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

    public function getNormalizedPower(): ?int
    {
        return $this->normalizedPower;
    }

    public function updateNormalizedPower(int $normalizedPower): self
    {
        $this->normalizedPower = $normalizedPower;

        return $this;
    }

    public function exportForAITooling(): array
    {
        if (!$data = $this->getData()) {
            return [];
        }

        $streamTypeSpecificStats = match ($this->getStreamType()) {
            StreamType::HEART_RATE => [
                'maxHeartRate' => max($data),
                'minHeartRate' => min($data),
                'avgHeartRate' => round(array_sum($data) / count($data)),
            ],
            StreamType::WATTS => [
                'maxPower' => max($data),
                'minPower' => min($data),
                'avgPower' => round(array_sum($data) / count($data)),
                'normalizedPower' => $this->getNormalizedPower(),
            ],
            StreamType::VELOCITY => [
                'maxMeterPerSecond' => max($data),
                'minMeterPerSecond' => min($data),
                'avgMeterPerSecond' => round(array_sum($data) / count($data)),
            ],
            default => [],
        };

        return [
            'activityId' => $this->getActivityId()->toUnprefixedString(),
            'steamType' => $this->getStreamType()->value,
            'totalPoints' => count($data),
            'bestAverages' => $this->getBestAverages(),
            ...$streamTypeSpecificStats,
        ];
    }
}
