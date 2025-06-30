<?php

declare(strict_types=1);

namespace App\Domain\Integration\AI\Tool;

use App\Domain\Strava\Segment\SegmentId;
use App\Domain\Strava\Segment\SegmentRepository;
use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolProperty;

final class GetSegment extends Tool
{
    public function __construct(
        private readonly SegmentRepository $segmentRepository,
    ) {
        parent::__construct(
            'get_segment_by_id',
            'Retrieves a segment from the database by a given id',
        );
    }

    /**
     * @return \NeuronAI\Tools\ToolPropertyInterface[]
     *
     * @codeCoverageIgnore
     */
    protected function properties(): array
    {
        return [
            new ToolProperty(
                name: 'segmentId',
                type: PropertyType::STRING,
                description: 'The id of the segment.',
                required: true
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function __invoke(string $segmentId): array
    {
        $segmentId = SegmentId::fromUnprefixed($segmentId);

        return $this->segmentRepository->find($segmentId)->exportForAITooling();
    }
}
