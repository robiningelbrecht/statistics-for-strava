<?php

declare(strict_types=1);

namespace App\Domain\Gear\UpdateGear;

use App\Domain\Gear\GearId;
use App\Domain\Gear\GearStatus;
use App\Infrastructure\CQRS\Command\Deserialize\AsDeserializableCommand;
use App\Infrastructure\CQRS\Command\Deserialize\CouldNotDeserializeCommand;
use App\Infrastructure\CQRS\Command\Deserialize\DeserializableCommand;
use App\Infrastructure\CQRS\Command\DomainCommand;
use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Money;
use Money\Parser\DecimalMoneyParser;

#[AsDeserializableCommand('update-gear')]
final readonly class UpdateGear extends DomainCommand implements DeserializableCommand
{
    private function __construct(
        private GearId $gearId,
        private string $name,
        private bool $isRetired,
        private ?Money $purchasePrice,
    ) {
    }

    public static function fromPayload(array $payload): self
    {
        if (!isset($payload['gearId'], $payload['name'])
            || !is_string($payload['gearId'])
            || !is_string($payload['name'])) {
            throw CouldNotDeserializeCommand::invalidPayload();
        }

        $name = trim($payload['name']);
        if ('' === $name) {
            throw CouldNotDeserializeCommand::invalidPayload();
        }

        $status = $payload['status'] ?? GearStatus::ACTIVE->value;
        if (!is_string($status) || !$gearStatus = GearStatus::tryFrom($status)) {
            throw CouldNotDeserializeCommand::invalidPayload();
        }

        return new self(
            gearId: GearId::fromString($payload['gearId']),
            name: $name,
            isRetired: GearStatus::RETIRED === $gearStatus,
            purchasePrice: self::parsePurchasePrice($payload),
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function parsePurchasePrice(array $payload): ?Money
    {
        $amount = isset($payload['purchasePriceAmount']) ? trim((string) $payload['purchasePriceAmount']) : '';
        if ('' === $amount) {
            return null;
        }

        $currencyCode = isset($payload['purchasePriceCurrency']) && is_string($payload['purchasePriceCurrency']) && '' !== $payload['purchasePriceCurrency']
            ? $payload['purchasePriceCurrency']
            : 'EUR';

        try {
            return new DecimalMoneyParser(new ISOCurrencies())->parse($amount, new Currency($currencyCode));
        } catch (\Throwable) {
            throw CouldNotDeserializeCommand::invalidPayload();
        }
    }

    public function getGearId(): GearId
    {
        return $this->gearId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isRetired(): bool
    {
        return $this->isRetired;
    }

    public function getPurchasePrice(): ?Money
    {
        return $this->purchasePrice;
    }
}
