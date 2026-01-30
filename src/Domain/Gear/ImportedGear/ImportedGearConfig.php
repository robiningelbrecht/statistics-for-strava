<?php

declare(strict_types=1);

namespace App\Domain\Gear\ImportedGear;

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
        if (empty($config)) {
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

    public function enrichGearWithCustomData(ImportedGear $gear): ImportedGear
    {
        if (!$configForGear = array_find($this->config, fn (array $gearConfig): bool => $gearConfig['gearId'] === $gear->getId()->toUnprefixedString())) {
            return $gear;
        }

        if (empty($configForGear['purchasePrice'])) {
            return $gear;
        }

        return $gear->withPurchasePrice(new Money(
            amount: $configForGear['purchasePrice']['amountInCents'],
            currency: new Currency($configForGear['purchasePrice']['currency'])
        ));
    }
}
