<?php

declare(strict_types=1);

namespace App\Domain\Activity\UpdateActivity;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityName;
use App\Domain\Activity\Image\ImageStatus;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Gear\GearId;
use App\Infrastructure\CQRS\Command\Deserialize\AsDeserializableCommand;
use App\Infrastructure\CQRS\Command\Deserialize\CouldNotDeserializeCommand;
use App\Infrastructure\CQRS\Command\Deserialize\DeserializableCommand;
use App\Infrastructure\CQRS\Command\DomainCommand;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\String\Path;

#[AsDeserializableCommand(UpdateActivity::NAME)]
final readonly class UpdateActivity extends DomainCommand implements DeserializableCommand
{
    public const string NAME = 'update-activity';

    /**
     * @var array<string>
     */
    private const array SUPPORTED_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    /**
     * @param array<ActivityNewImage>     $newImages
     * @param array<ActivityRemovedImage> $removedImages
     */
    private function __construct(
        private ActivityId $activityId,
        private ActivityName $name,
        private SportType $sportType,
        private ?string $description,
        private ?string $deviceName,
        private ?GearId $gearId,
        private bool $isCommute,
        private array $newImages,
        private array $removedImages,
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

        $newImages = [];
        $removedImages = [];
        if (array_key_exists('images', $payload)) {
            if (!is_string($payload['images'])) {
                throw CouldNotDeserializeCommand::invalidPayload('The "images" field is invalid.');
            }

            $decodedImages = Json::decode($payload['images']);
            if (!is_array($decodedImages)) {
                throw CouldNotDeserializeCommand::invalidPayload('The "images" field is invalid.');
            }

            foreach ($decodedImages as $image) {
                if (!is_array($image) || !isset($image['status']) || !is_string($image['status']) || !$status = ImageStatus::tryFrom($image['status'])) {
                    throw CouldNotDeserializeCommand::invalidPayload('Each image requires a valid "status".');
                }

                if (ImageStatus::NEW === $status) {
                    if (!isset($image['filename'], $image['content']) || !is_string($image['filename']) || !is_string($image['content'])) {
                        throw CouldNotDeserializeCommand::invalidPayload('A new image requires a "filename" and "content".');
                    }

                    $filename = Path::fromString(trim($image['filename']));
                    if (!in_array($filename->getExtension(), self::SUPPORTED_IMAGE_EXTENSIONS, true)) {
                        throw CouldNotDeserializeCommand::invalidPayload('Unsupported image file type.');
                    }

                    $content = base64_decode($image['content'], true);
                    if (false === $content || '' === $content) {
                        throw CouldNotDeserializeCommand::invalidPayload('A new image has invalid "content".');
                    }

                    $newImages[] = new ActivityNewImage(
                        filename: $filename,
                        content: $content
                    );
                }
                if (ImageStatus::REMOVED === $status) {
                    if (!isset($image['path']) || !is_string($image['path']) || '' === trim($image['path'])) {
                        throw CouldNotDeserializeCommand::invalidPayload('A removed image requires a "path".');
                    }
                    $removedImages[] = new ActivityRemovedImage(trim($image['path']));
                }
            }
        }

        return new self(
            activityId: ActivityId::fromString($payload['activityId']),
            name: ActivityName::fromString($name),
            sportType: $sportType,
            description: '' !== $description ? $description : null,
            deviceName: '' !== $deviceName ? $deviceName : null,
            gearId: $gearId,
            isCommute: filter_var($payload['isCommute'] ?? false, FILTER_VALIDATE_BOOLEAN),
            newImages: $newImages,
            removedImages: $removedImages,
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

    /**
     * @return array<ActivityNewImage>
     */
    public function getNewImages(): array
    {
        return $this->newImages;
    }

    /**
     * @return array<ActivityRemovedImage>
     */
    public function getRemovedImages(): array
    {
        return $this->removedImages;
    }
}
