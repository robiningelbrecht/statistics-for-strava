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
        $config = WidgetConfiguration::empty()
            ->add('context', 'distance');
        yield 'missing "enableLastXYearsByDefault" key' => [$config, 'Configuration item "enableLastXYearsByDefault" is required for MonthlyStatsWidget.'];

        $config = WidgetConfiguration::empty()
            ->add('enableLastXYearsByDefault', 'invalid')
            ->add('context', 'distance');
        yield 'invalid "enableLastXYearsByDefault" key' => [$config, 'Configuration item "enableLastXYearsByDefault" must be an integer.'];

        $config = WidgetConfiguration::empty()
            ->add('enableLastXYearsByDefault', 10);
        yield 'missing "context" key' => [$config, 'Configuration item "context" is required for MonthlyStatsWidget.'];

        $config = WidgetConfiguration::empty()
            ->add('enableLastXYearsByDefault', 10)
            ->add('context', 'invalid');
        yield 'invalid "context" key' => [$config, 'Invalid context "invalid" provided for MonthlyStatsWidget.'];

        $config = WidgetConfiguration::empty()
            ->add('enableLastXYearsByDefault', 10)
            ->add('context', false);
        yield '"context" must be string' => [$config, 'Configuration item "context" must be a string.'];
    }

    protected function setUp(): void
    {
        $this->widget = $this->getContainer()->get(MonthlyStatsWidget::class);
    }
}
