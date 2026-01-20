<?php

declare(strict_types=1);

namespace App\Application\Import\CalculateActivityMetrics\Pipeline;

use App\Domain\Activity\Stream\ActivityStream;
use App\Domain\Activity\Stream\ActivityStreams;
use App\Domain\Activity\Stream\StreamType;

final readonly class RamerDouglasPeucker
{
    public function __construct(
        private ActivityStream $distanceStream,
        private ?ActivityStream $movingStream,
        private ActivityStreams $otherStreams,
    ) {
    }

    /**
     * @return array<mixed>
     */
    public function applyWith(Epsilon $epsilon): array
    {
        if (!$distances = $this->distanceStream->getData()) {
            throw new \InvalidArgumentException('Distance stream is empty');
        }

        $rawPoints = [];

        $movingIndexes = $this->movingStream?->getData();
        $velocityData = $this->otherStreams->filterOnType(StreamType::VELOCITY)?->getData() ?? [];
        foreach ($distances as $i => $distance) {
            if (!empty($movingIndexes) && false === $movingIndexes[$i]) {
                // Athlete was not moving.
                continue;
            }

            if (!empty($velocityData) && $velocityData[$i] < 0.5) {
                // VERY slow velocity data, athlete was probably not moving.
                // Consider this invalid data.
                continue;
            }

            $otherPoints = [];
            /** @var ActivityStream $otherStream */
            foreach ($this->otherStreams as $otherStream) {
                $otherPoints[] = $otherStream->getData()[$i] ?? 0;
            }

            $rawPoints[] = [
                $distance,
                ...$otherPoints,
            ];
        }

        return $this->simplify($rawPoints, $epsilon->toFloat());
    }

    /**
     * @param array<int, array<int, int|float>> $points ,
     *
     * @return array<mixed>
     */
    private function simplify(array $points, float $epsilon): array
    {
        $count = count($points);
        if ($count < 3) {
            return $points;
        }

        // Always keep first and last points
        $first = 0;
        $last = $count - 1;
        $stack = [[$first, $last]];

        $keep = array_fill(0, $count, false);
        $keep[$first] = true;
        $keep[$last] = true;

        while (!empty($stack)) {
            [$start, $end] = array_pop($stack);

            $dMax = 0.0;
            $index = $start;

            for ($i = $start + 1; $i < $end; ++$i) {
                $d = $this->getPointToLineDistance($points[$i], $points[$start], $points[$end]);
                if ($d > $dMax) {
                    $index = $i;
                    $dMax = $d;
                }
            }

            if ($dMax > $epsilon) {
                $keep[$index] = true;
                $stack[] = [$start, $index];
                $stack[] = [$index, $end];
            }
        }

        $result = [];
        for ($i = 0; $i < $count; ++$i) {
            if ($keep[$i]) {
                $result[] = $points[$i];
            }
        }

        return $result;
    }

    /**
     * @param array<int, int|float> $point
     * @param array<int, int|float> $lineStart
     * @param array<int, int|float> $lineEnd
     */
    private function getPointToLineDistance(
        array $point,
        array $lineStart,
        array $lineEnd): float
    {
        $dotProduct = 0.0;
        $lineLengthSq = 0.0;
        $dimension = count($point);

        // Compute dot product and line length squared
        for ($i = 0; $i < $dimension; ++$i) {
            $lineVector = $lineEnd[$i] - $lineStart[$i];
            $pointVector = $point[$i] - $lineStart[$i];

            $dotProduct += $pointVector * $lineVector;
            $lineLengthSq += $lineVector * $lineVector;
        }

        // Projection factor clamped to [0,1]
        $t = (0.0 !== $lineLengthSq) ? $dotProduct / $lineLengthSq : 0.0;
        if ($t < 0.0) {
            $t = 0.0;
        } elseif ($t > 1.0) {
            $t = 1.0;
        }

        // Compute squared distance from point to the closest point on line
        $distSq = 0.0;
        for ($i = 0; $i < $dimension; ++$i) {
            $lineVector = $lineEnd[$i] - $lineStart[$i];
            $closest = $lineStart[$i] + $t * $lineVector;
            $diff = $point[$i] - $closest;
            $distSq += $diff * $diff;
        }

        return sqrt($distSq);
    }
}
