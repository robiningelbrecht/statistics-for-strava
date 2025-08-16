<?php

namespace App\Tests\BuildApp\BuildDashboardHtml\Layout\Widget;

use App\BuildApp\BuildDashboardHtml\Layout\InvalidDashboardLayout;
use App\BuildApp\BuildDashboardHtml\Layout\Widget\MonthlyStatsWidget;
use App\Tests\ContainerTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class MonthlyStatsWidgetTest extends ContainerTestCase
{
    private MonthlyStatsWidget $widget;

    #[DataProvider(methodName: 'provideInvalidConfig')]
    public function testGuardValidConfigurationItShouldThrow(array $config, string $expectedException): void
    {
        $this->expectExceptionObject(new InvalidDashboardLayout($expectedException));
        $this->widget->guardValidConfiguration($config);
    }

    public static function provideInvalidConfig(): iterable
    {
        $config = [
            'context' => 'distance',
        ];
        yield 'missing "enableLastXYearsByDefault" key' => [$config, 'Configuration item "enableLastXYearsByDefault" is required for MonthlyStatsWidget.'];

        $config = [
            'enableLastXYearsByDefault' => 'invalid',
        ];
        yield 'invalid "enableLastXYearsByDefault" key' => [$config, 'Configuration item "enableLastXYearsByDefault" must be an integer.'];

        $config = [
            'enableLastXYearsByDefault' => 3,
        ];
        yield 'missing "context" key' => [$config, 'Configuration item "context" is required for MonthlyStatsWidget.'];

        $config = [
            'enableLastXYearsByDefault' => 3,
            'context' => 'invalid',
        ];
        yield 'invalid "context" key' => [$config, 'Invalid context "invalid" provided for MonthlyStatsWidget.'];
    }

    protected function setUp(): void
    {
        $this->widget = $this->getContainer()->get(MonthlyStatsWidget::class);
    }
}
