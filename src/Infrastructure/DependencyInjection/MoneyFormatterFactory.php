<?php

declare(strict_types=1);

namespace App\Infrastructure\DependencyInjection;

use App\Infrastructure\Localisation\Locale;
use Money\Currencies\ISOCurrencies;
use Money\Formatter\IntlMoneyFormatter;
use Money\MoneyFormatter;

final readonly class MoneyFormatterFactory
{
    public function __construct(
        private Locale $locale,
    ) {
    }

    public function __invoke(): MoneyFormatter
    {
        $numberFormatter = new \NumberFormatter(
            locale: $this->locale->value,
            style: \NumberFormatter::CURRENCY
        );

        return new IntlMoneyFormatter(
            formatter: $numberFormatter,
            currencies: new ISOCurrencies()
        );
    }
}
