<?php

declare(strict_types=1);

namespace App\Infrastructure\Twig;

use Money\Currencies\ISOCurrencies;
use Money\Formatter\DecimalMoneyFormatter;
use Money\Money;
use Money\MoneyFormatter;
use Twig\Attribute\AsTwigFilter;
use Twig\Attribute\AsTwigFunction;

final readonly class MoneyTwigExtension
{
    private ISOCurrencies $currencies;
    private DecimalMoneyFormatter $decimalMoneyFormatter;

    public function __construct(
        private MoneyFormatter $moneyFormatter,
    ) {
        $this->currencies = new ISOCurrencies();
        $this->decimalMoneyFormatter = new DecimalMoneyFormatter($this->currencies);
    }

    #[AsTwigFilter('formatMoney')]
    public function doMoneyFormat(Money $money): string
    {
        return $this->moneyFormatter->format($money);
    }

    #[AsTwigFilter('formatMoneyDecimal')]
    public function doMoneyDecimalFormat(?Money $money): string
    {
        if (!$money instanceof Money) {
            return '';
        }

        return $this->decimalMoneyFormatter->format($money);
    }

    /**
     * @return list<string>
     */
    #[AsTwigFunction('currencies')]
    public function getCurrencies(): array
    {
        $codes = [];
        foreach ($this->currencies as $currency) {
            $codes[] = $currency->getCode();
        }
        sort($codes);

        return $codes;
    }
}
