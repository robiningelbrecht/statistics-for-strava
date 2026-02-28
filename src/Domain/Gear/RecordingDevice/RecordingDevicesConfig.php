<?php

declare(strict_types=1);

namespace App\Domain\Gear\RecordingDevice;

use Money\Currency;
use Money\Money;

final readonly class RecordingDevicesConfig
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
                throw new InvalidRecordingDevicesConfig('"gearId" property is required for each recording device');
            }
            if (isset($gear['purchasePrice']) && empty($gear['purchasePrice']['amountInCents'])) {
                throw new InvalidRecordingDevicesConfig('"purchasePrice.amountInCents" property must be a numeric value');
            }
            if (isset($gear['purchasePrice']) && !is_numeric($gear['purchasePrice']['amountInCents'])) {
                throw new InvalidRecordingDevicesConfig('"purchasePrice.amountInCents" property must be a numeric value');
            }
            if (isset($gear['purchasePrice']) && empty($gear['purchasePrice']['currency'])) {
                throw new InvalidRecordingDevicesConfig('"purchasePrice.currency" property is required');
            }
        }

        return new self($config);
    }

    public function getPurchasePrice(string $recordingDeviceId): ?Money
    {
        if (!$configForDevice = array_find($this->config, fn (array $deviceConfig): bool => $deviceConfig['gearId'] === $recordingDeviceId)) {
            return null;
        }
        if (empty($configForDevice['purchasePrice'])) {
            return null;
        }

        return new Money(
            amount: $configForDevice['purchasePrice']['amountInCents'],
            currency: new Currency($configForDevice['purchasePrice']['currency'])
        );
    }
}
