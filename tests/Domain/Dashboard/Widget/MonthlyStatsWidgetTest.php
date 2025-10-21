<?php

namespace App\Tests\Domain\Dashboard\Widget;

use App\Domain\Dashboard\InvalidDashboardLayout;
use App\Domain\Dashboard\Widget\MonthlyStatsWidget;
use App\Domain\Dashboard\Widget\WidgetConfiguration;
use App\Tests\ContainerTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class MonthlyStatsWidgetTest extends ContainerTestCase
{
    private MonthlyStatsWidget $widget;

    #[DataProvider(methodName: 'provideInvalidConfig')]
    public function testGuardValidConfigurationItShouldThrow(WidgetConfiguration $config, string $expectedException): void
    {
        $this->expectExceptionObject(new InvalidDashboardLayout($expectedException));
        $this->widget->guardValidConfiguration($config);
    }

    public static function provideInvalidConfig(): iterable
    {
        $config = WidgetConfiguration::empty();
        yield 'missing "enableLastXYearsByDefault" key' => [$config, 'Configuration item "enableLastXYearsByDefault" is required for MonthlyStatsWidget.'];

        $config = WidgetConfiguration::empty()
            ->add('enableLastXYearsByDefault', 'invalid');
        yield 'invalid "enableLastXYearsByDefault" key' => [$config, 'Configuration item "enableLastXYearsByDefault" must be an integer.'];
    }

    protected function setUp(): void
    {
        $this->widget = $this->getContainer()->get(MonthlyStatsWidget::class);
    }
}
