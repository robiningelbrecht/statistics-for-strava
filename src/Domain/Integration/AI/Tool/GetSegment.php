<?php

declare(strict_types=1);

namespace App\Domain\Integration\AI\Tool;

use App\Domain\Segment\SegmentId;
use App\Domain\Segment\SegmentRepository;
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
            <<<DESC
            Retrieves detailed information about a specific segment using its ID.
            Use this tool when the user asks about a particular segment. (e.g., “Tell me more about segment 1234” or “What’s the length of that climb?”).
            Returns segment data such as name, distance, elevation, sport type and climb category.
            DESC
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
