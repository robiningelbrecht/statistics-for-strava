<?php

declare(strict_types=1);

namespace App\Domain\Activity\UpdateActivity;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityName;
use App\Infrastructure\CQRS\Command\Deserialize\AsDeserializableCommand;
use App\Infrastructure\CQRS\Command\Deserialize\CouldNotDeserializeCommand;
use App\Infrastructure\CQRS\Command\Deserialize\DeserializableCommand;
use App\Infrastructure\CQRS\Command\DomainCommand;

#[AsDeserializableCommand('update-activity')]
final readonly class UpdateActivity extends DomainCommand implements DeserializableCommand
{
    private function __construct(
        private ActivityId $activityId,
        private ActivityName $name,
    ) {
    }

    public static function fromPayload(array $payload): self
    {
        if (!isset($payload['activityId'], $payload['name'])
            || !is_string($payload['activityId'])
            || !is_string($payload['name'])) {
            throw CouldNotDeserializeCommand::invalidPayload('An "activityId" and "name" are required.');
        }

        $name = trim($payload['name']);
        if ('' === $name) {
            throw CouldNotDeserializeCommand::invalidPayload('The name cannot be empty.');
        }

        return new self(
            activityId: ActivityId::fromString($payload['activityId']),
            name: ActivityName::fromString($name),
        );
    }

    public function getActivityId(): ActivityId
    {
        return $this->activityId;
    }

    public function getName(): ActivityName
    {
        return $this->name;
    }
}
