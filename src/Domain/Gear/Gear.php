<?php

declare(strict_types=1);

namespace App\Domain\Gear;

use App\Domain\Activity\ActivityTypes;
use App\Domain\Integration\AI\SupportsAITooling;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Money\Money;

interface Gear extends SupportsAITooling
{
    public function getId(): GearId;

    public function withName(string $name): self;

    public function getOriginalName(): string;

    public function getName(): string;

    public function getDistance(): Kilometer;

    public function isRetired(): bool;

    public function withIsRetired(bool $isRetired): self;

    public function withDistance(Meter $distance): self;

    public function getCreatedOn(): SerializableDateTime;

    public function getImageSrc(): ?string;

    public function getPurchasePrice(): ?Money;

    public function getActivityTypes(): ActivityTypes;

    public function withActivityTypes(ActivityTypes $activityTypes): self;

    public function withImageSrc(string $imageSrc): self;

    public function withPurchasePrice(Money $purchasePrice): self;
}
