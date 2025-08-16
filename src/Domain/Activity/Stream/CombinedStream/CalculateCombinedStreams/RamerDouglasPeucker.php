<?php

declare(strict_types=1);

namespace App\Domain\Activity\Stream\CombinedStream\CalculateCombinedStreams;

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
        if (count($points) < 3) {
            return $points;
        }

        $dMax = 0;
        $index = 0;
        $end = count($points) - 1;

        for ($i = 1; $i < $end; ++$i) {
            $d = $this->getPointToLineDistance($points[$i], $points[0], $points[$end]);
            if ($d > $dMax) {
                $index = $i;
                $dMax = $d;
            }
        }

        if ($dMax > $epsilon) {
            $firstHalf = $this->simplify(array_slice($points, 0, $index + 1), $epsilon);
            $secondHalf = $this->simplify(array_slice($points, $index), $epsilon);

            return array_merge(array_slice($firstHalf, 0, -1), $secondHalf);
        }

        return [$points[0], $points[$end]];
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
        $lineVector = [];
        $pointVector = [];

        for ($i = 0; $i < count($point); ++$i) {
            $lineVector[] = $lineEnd[$i] - $lineStart[$i];
            $pointVector[] = $point[$i] - $lineStart[$i];
        }

        $dotProduct = array_sum(array_map(fn (float $a, float $b) => $a * $b, $pointVector, $lineVector));
        $lineLengthSq = array_sum(array_map(fn (float $a) => $a * $a, $lineVector));

        $t = (0 != $lineLengthSq) ? $dotProduct / $lineLengthSq : 0;
        $t = max(0, min(1, $t)); // Clamp to segment bounds

        $closestPoint = [];
        for ($i = 0; $i < count($point); ++$i) {
            $closestPoint[] = $lineStart[$i] + $t * $lineVector[$i];
        }

        return sqrt(array_sum(array_map(fn (float $a, float $b) => ($a - $b) ** 2, $point, $closestPoint)));
    }
}
