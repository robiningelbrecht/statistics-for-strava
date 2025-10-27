<?php

namespace App\Tests\Infrastructure\Twig;

use App\Infrastructure\Twig\MoneyTwigExtension;
use App\Tests\ContainerTestCase;
use Money\Money;

class MoneyTwigExtensionTest extends ContainerTestCase
{
    private MoneyTwigExtension $moneyTwigExtension;

    public function testDoMoneyFormat(): void
    {
        $this->assertEquals(
            '€1.00',
            $this->moneyTwigExtension->doMoneyFormat(Money::EUR(100))
        );
    }

    protected function setUp(): void
    {
        $this->moneyTwigExtension = $this->getContainer()->get(MoneyTwigExtension::class);
    }
}
