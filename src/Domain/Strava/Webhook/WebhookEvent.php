<?php

declare(strict_types=1);

namespace App\Domain\Strava\Webhook;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
final readonly class WebhookEvent
{
    /**
     * @param array<string, mixed> $payload
     */
    private function __construct(
        #[ORM\Id, ORM\Column(type: 'string', unique: true)]
        private string $objectId,
        #[ORM\Column(type: 'string')]
        private string $objectType,
        #[ORM\Column(type: 'string')]
        private WebhookAspectType $aspectType,
        #[ORM\Column(type: 'json')]
        private array $payload,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function create(
        string $objectId,
        string $objectType,
        WebhookAspectType $aspectType,
        array $payload,
    ): self {
        return new self(
            objectId: $objectId,
            objectType: $objectType,
            aspectType: $aspectType,
            payload: $payload,
        );
    }

    public function getObjectId(): string
    {
        return $this->objectId;
    }

    public function getObjectType(): string
    {
        return $this->objectType;
    }

    public function getAspectType(): WebhookAspectType
    {
        return $this->aspectType;
    }

    /**
     * @return array<string, mixed>
     */
    public function getPayload(): array
    {
        return $this->payload;
    }
}
