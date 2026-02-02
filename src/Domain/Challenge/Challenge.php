<?php

namespace App\Domain\Challenge;

use App\Domain\Integration\AI\SupportsAITooling;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Index(name: 'Challenge_createdOnIndex', columns: ['createdOn'])]
final readonly class Challenge implements SupportsAITooling
{
    private function __construct(
        #[ORM\Id, ORM\Column(type: 'string', unique: true)]
        private ChallengeId $challengeId,
        #[ORM\Column(type: 'datetime_immutable')]
        private SerializableDateTime $createdOn,
        #[ORM\Column(type: 'string')]
        private string $name,
        #[ORM\Column(type: 'string', nullable: true)]
        private ?string $logoUrl,
        #[ORM\Column(type: 'string', nullable: true)]
        private ?string $localLogoUrl,
        #[ORM\Column(type: 'string')]
        private string $slug,
    ) {
    }

    public static function fromState(
        ChallengeId $challengeId,
        SerializableDateTime $createdOn,
        string $name,
        ?string $logoUrl,
        ?string $localLogoUrl,
        string $slug,
    ): self {
        return new self(
            challengeId: $challengeId,
            createdOn: $createdOn,
            name: $name,
            logoUrl: $logoUrl,
            localLogoUrl: $localLogoUrl,
            slug: $slug,
        );
    }

    public static function create(
        ChallengeId $challengeId,
        SerializableDateTime $createdOn,
        string $name,
        ?string $logoUrl,
        string $slug,
    ): self {
        return new self(
            challengeId: $challengeId,
            createdOn: $createdOn,
            name: $name,
            logoUrl: $logoUrl,
            localLogoUrl: null,
            slug: $slug,
        );
    }

    public function getId(): ChallengeId
    {
        return $this->challengeId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLogoUrl(): ?string
    {
        return $this->logoUrl;
    }

    public function getLocalLogoUrl(): ?string
    {
        if (null === $this->localLogoUrl) {
            return null;
        }
        if (str_starts_with($this->localLogoUrl, '/')) {
            return $this->localLogoUrl;
        }

        return '/'.$this->localLogoUrl;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getUrl(): string
    {
        return 'https://www.strava.com/challenges/'.$this->getSlug();
    }

    public function withLocalLogo(string $path): self
    {
        return clone ($this, [
            'localLogoUrl' => $path,
        ]);
    }

    public function getCreatedOn(): SerializableDateTime
    {
        return $this->createdOn;
    }

    /**
     * @return array<string, mixed>
     */
    public function exportForAITooling(): array
    {
        return [
            'id' => $this->getId()->toUnprefixedString(),
            'obtainedOn' => $this->getCreatedOn()->format('Y-m-d'),
            'name' => $this->getName(),
            'slug' => $this->getSlug(),
        ];
    }
}
