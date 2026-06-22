<?php

declare(strict_types=1);

namespace App\Domain\Gear\ImportedGear;

use App\Domain\Gear\GearId;
use Money\Currency;
use Money\Money;

final readonly class ImportedGearConfig
{
    private function __construct(
        /** @var array<string, mixed> */
        private array $config,
    ) {
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function fromArray(
        array $config,
    ): self {
        if ([] === $config) {
            return new self([]);
        }

        foreach ($config as $gear) {
            if (empty($gear['gearId'])) {
                throw new InvalidImportedGearConfig('"gearId" property is required for each gear');
            }
            if (isset($gear['purchasePrice']) && empty($gear['purchasePrice']['amountInCents'])) {
                throw new InvalidImportedGearConfig('"purchasePrice.amountInCents" property must be a numeric value');
            }
            if (isset($gear['purchasePrice']) && !is_numeric($gear['purchasePrice']['amountInCents'])) {
                throw new InvalidImportedGearConfig('"purchasePrice.amountInCents" property must be a numeric value');
            }
            if (isset($gear['purchasePrice']) && empty($gear['purchasePrice']['currency'])) {
                throw new InvalidImportedGearConfig('"purchasePrice.currency" property is required');
            }
        }

        return new self($config);
    }

    public function getPurchasePrice(GearId $gearId): ?Money
    {
        if (!$configForGear = array_find($this->config, fn (array $gearConfig): bool => $gearConfig['gearId'] === $gearId->toUnprefixedString())) {
            return null;
        }

        if (empty($configForGear['purchasePrice'])) {
            return null;
        }

        return new Money(
            amount: $configForGear['purchasePrice']['amountInCents'],
            currency: new Currency($configForGear['purchasePrice']['currency'])
        );
    }
}
