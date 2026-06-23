<?php

declare(strict_types=1);

namespace App\Domain\Gear;

use App\Infrastructure\CQRS\Command\Deserialize\CouldNotDeserializeCommand;
use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Money;
use Money\Parser\DecimalMoneyParser;

trait ProvidePurchasePriceFromPayload
{
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
            throw CouldNotDeserializeCommand::invalidPayload('The purchase price is invalid.');
        }
    }
}
