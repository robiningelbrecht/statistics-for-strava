<?php

declare(strict_types=1);

namespace App\Infrastructure\Twig;

use Money\Money;
use Money\MoneyFormatter;
use Twig\Attribute\AsTwigFilter;

final readonly class MoneyTwigExtension
{
    public function __construct(
        private MoneyFormatter $moneyFormatter,
    ) {
    }

    #[AsTwigFilter('formatMoney')]
    public function doMoneyFormat(Money $money): string
    {
        return $this->moneyFormatter->format($money);
    }
}
