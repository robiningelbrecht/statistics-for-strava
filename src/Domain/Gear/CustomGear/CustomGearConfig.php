<?php

declare(strict_types=1);

namespace App\Domain\Gear\CustomGear;

use App\Domain\Gear\GearId;
use App\Domain\Gear\GearIds;
use App\Domain\Gear\GearType;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\String\HashtagPrefix;
use App\Infrastructure\ValueObject\String\Name;
use App\Infrastructure\ValueObject\String\Tag;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Money\Currency;
use Money\Money;

final readonly class CustomGearConfig
{
    private CustomGears $customGears;

    private function __construct(
        private bool $isFeatureEnabled,
        private HashtagPrefix $hashtagPrefix,
    ) {
        $this->customGears = CustomGears::empty();
    }

    /**
     * @param array<string, mixed>|null $config
     */
    public static function fromArray(
        ?array $config,
    ): self {
        if (empty($config)) {
            return new self(
                isFeatureEnabled: false,
                hashtagPrefix: HashtagPrefix::fromString('dummy'),
            );
        }

        foreach (['enabled', 'hashtagPrefix', 'customGears'] as $requiredKey) {
            if (array_key_exists($requiredKey, $config)) {
                continue;
            }
            throw new InvalidCustomGearConfig(sprintf('"%s" property is required', $requiredKey));
        }

        if (!is_array($config['customGears'])) {
            throw new InvalidCustomGearConfig('"customGears" property must be an array');
        }

        $customGearConfig = new self(
            isFeatureEnabled: $config['enabled'],
            hashtagPrefix: HashtagPrefix::fromString($config['hashtagPrefix']),
        );

        foreach ($config['customGears'] as $customGear) {
            foreach (['tag', 'label', 'isRetired'] as $requiredKey) {
                if (array_key_exists($requiredKey, $customGear)) {
                    continue;
                }
                throw new InvalidCustomGearConfig(sprintf('"%s" property is required for each custom gear', $requiredKey));
            }

            if (!is_bool($customGear['isRetired'])) {
                throw new InvalidCustomGearConfig('"isRetired" property must be a boolean');
            }

            if (isset($customGear['purchasePrice']) && empty($customGear['purchasePrice']['amountInCents'])) {
                throw new InvalidCustomGearConfig('"purchasePrice.amountInCents" property must be a numeric value');
            }
            if (isset($customGear['purchasePrice']) && !is_numeric($customGear['purchasePrice']['amountInCents'])) {
                throw new InvalidCustomGearConfig('"purchasePrice.amountInCents" property must be a numeric value');
            }
            if (isset($customGear['purchasePrice']) && empty($customGear['purchasePrice']['currency'])) {
                throw new InvalidCustomGearConfig('"purchasePrice.currency" property is required');
            }

            $gear = CustomGear::create(
                gearId: GearId::fromUnprefixed($customGear['tag']),
                type: GearType::CUSTOM,
                distanceInMeter: Meter::zero(),
                createdOn: SerializableDateTime::some(),
                name: (string) Name::fromString($customGear['label']),
                isRetired: $customGear['isRetired']
            );
            $gear = $gear->withFullTag(Tag::fromTags(
                (string) HashtagPrefix::fromString($config['hashtagPrefix']),
                $customGear['tag'])
            );
            if (isset($customGear['purchasePrice'])) {
                $gear->enrichWithPurchasePrice(new Money(
                    amount: $customGear['purchasePrice']['amountInCents'],
                    currency: new Currency($customGear['purchasePrice']['currency'])
                ));
            }
            $customGearConfig->addCustomGear($gear);
        }

        $customGearTags = array_count_values(array_column($config['customGears'], 'tag'));
        if ($duplicates = array_keys(array_filter($customGearTags, fn (int $count): bool => $count > 1))) {
            throw new InvalidCustomGearConfig(sprintf('duplicate custom gear tags found: %s', implode(', ', $duplicates)));
        }

        return $customGearConfig;
    }

    public function isFeatureEnabled(): bool
    {
        return $this->isFeatureEnabled;
    }

    public function getHashtagPrefix(): HashtagPrefix
    {
        return $this->hashtagPrefix;
    }

    public function getGearIds(): GearIds
    {
        return $this->customGears->getGearIds();
    }

    public function addCustomGear(CustomGear $gear): void
    {
        $this->customGears->add($gear);
    }

    public function getCustomGears(): CustomGears
    {
        return $this->customGears;
    }

    /**
     * @return string[]
     */
    public function getAllGearTags(): array
    {
        return $this->getCustomGears()->getAllGearTags();
    }

    public function enrichGearWithCustomData(CustomGear $gear): CustomGear
    {
        $gear = $gear->withFullTag(Tag::fromTags(
            (string) $this->getHashtagPrefix(),
            $gear->getId()->toUnprefixedString()
        ));

        if (!$configuredCustomGear = array_find($this->customGears->toArray(), fn (CustomGear $customGear): bool => $customGear->getId()->toUnprefixedString() === $gear->getId()->toUnprefixedString())) {
            return $gear;
        }

        if (!$configuredCustomGear->getPurchasePrice()) {
            return $gear;
        }

        $gear->enrichWithPurchasePrice($configuredCustomGear->getPurchasePrice());

        return $gear;
    }
}
