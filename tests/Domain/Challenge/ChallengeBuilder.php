<?php

declare(strict_types=1);

namespace App\Tests\Domain\Challenge;

use App\Domain\Challenge\Challenge;
use App\Domain\Challenge\ChallengeId;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final class ChallengeBuilder
{
    private ChallengeId $challengeId;
    private SerializableDateTime $createdOn;
    private string $name = 'Challenge';
    private ?string $logoUrl = null;
    private ?string $localLogoUrl = null;
    private string $slug = 'challenge';

    private function __construct()
    {
        $this->challengeId = ChallengeId::fromUnprefixed('test');
        $this->createdOn = SerializableDateTime::fromString('2023-10-10');
    }

    public static function fromDefaults(): self
    {
        return new self();
    }

    public function build(): Challenge
    {
        return Challenge::fromState(
            challengeId: $this->challengeId,
            createdOn: $this->createdOn,
            name: $this->name,
            logoUrl: $this->logoUrl,
            localLogoUrl: $this->localLogoUrl,
            slug: $this->slug,
        );
    }

    public function withChallengeId(ChallengeId $challengeId): self
    {
        $this->challengeId = $challengeId;

        return $this;
    }

    public function withCreatedOn(SerializableDateTime $createdOn): self
    {
        $this->createdOn = $createdOn;

        return $this;
    }

    public function withName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function withLogoUrl(string $logoUrl): self
    {
        $this->logoUrl = $logoUrl;

        return $this;
    }

    public function withSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function withLocalLogoUrl(string $localLogoUrl): self
    {
        $this->localLogoUrl = $localLogoUrl;

        return $this;
    }
}
