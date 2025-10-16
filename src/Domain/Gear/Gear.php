<?php

declare(strict_types=1);

namespace App\Domain\Gear;

use App\Domain\Activity\SportType\SportTypes;
use App\Domain\Integration\AI\SupportsAITooling;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

interface Gear extends SupportsAITooling
{
    public function getId(): GearId;

    public function updateName(string $name): self;

    public function getOriginalName(): string;

    public function getName(): string;

    public function getDistance(): Kilometer;

    public function isRetired(): bool;

    public function updateIsRetired(bool $isRetired): self;

    public function updateDistance(Meter $distance): self;

    public function getCreatedOn(): SerializableDateTime;

    public function getImageSrc(): ?string;

    public function getSportTypes(): SportTypes;

    public function hasAtLeastOneSportType(SportTypes $sportTypesToCheck): bool;

    public function enrichWithSportTypes(SportTypes $sportTypes): self;

    public function enrichWithImageSrc(string $imageSrc): self;
}
