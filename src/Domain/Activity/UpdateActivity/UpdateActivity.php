<?php

declare(strict_types=1);

namespace App\Domain\Activity\UpdateActivity;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityName;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Gear\GearId;
use App\Infrastructure\CQRS\Command\Deserialize\AsDeserializableCommand;
use App\Infrastructure\CQRS\Command\Deserialize\CouldNotDeserializeCommand;
use App\Infrastructure\CQRS\Command\Deserialize\DeserializableCommand;
use App\Infrastructure\CQRS\Command\DomainCommand;

#[AsDeserializableCommand(UpdateActivity::NAME)]
final readonly class UpdateActivity extends DomainCommand implements DeserializableCommand
{
    public const string NAME = 'update-activity';

    private function __construct(
        private ActivityId $activityId,
        private ActivityName $name,
        private SportType $sportType,
        private ?string $description,
        private ?string $deviceName,
        private ?GearId $gearId,
        private bool $isCommute,
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

        if (!isset($payload['sportType']) || !is_string($payload['sportType']) || !$sportType = SportType::tryFrom($payload['sportType'])) {
            throw CouldNotDeserializeCommand::invalidPayload('A valid "sportType" is required.');
        }

        $description = isset($payload['description']) && is_string($payload['description']) ? trim($payload['description']) : '';
        $deviceName = isset($payload['deviceName']) && is_string($payload['deviceName']) ? trim($payload['deviceName']) : '';

        $gearId = isset($payload['gearId']) && is_string($payload['gearId']) && '' !== trim($payload['gearId'])
            ? GearId::fromString(trim($payload['gearId']))
            : null;

        return new self(
            activityId: ActivityId::fromString($payload['activityId']),
            name: ActivityName::fromString($name),
            sportType: $sportType,
            description: '' !== $description ? $description : null,
            deviceName: '' !== $deviceName ? $deviceName : null,
            gearId: $gearId,
            isCommute: filter_var($payload['isCommute'] ?? false, FILTER_VALIDATE_BOOLEAN),
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

    public function getSportType(): SportType
    {
        return $this->sportType;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getDeviceName(): ?string
    {
        return $this->deviceName;
    }

    public function getGearId(): ?GearId
    {
        return $this->gearId;
    }

    public function isCommute(): bool
    {
        return $this->isCommute;
    }
}
