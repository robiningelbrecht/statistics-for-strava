<?php

namespace App\Tests\Domain\Dashboard\Widget;

use App\Domain\Dashboard\InvalidDashboardLayout;
use App\Domain\Dashboard\Widget\GearStatsWidget;
use App\Tests\ContainerTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class GearStatsWidgetTest extends ContainerTestCase
{
    private GearStatsWidget $widget;

    #[DataProvider(methodName: 'provideInvalidConfig')]
    public function testGuardValidConfigurationItShouldThrow(array $config, string $expectedException): void
    {
        $this->expectExceptionObject(new InvalidDashboardLayout($expectedException));
        $this->widget->guardValidConfiguration($config);
    }

    public static function provideInvalidConfig(): iterable
    {
        yield 'missing "includeRetiredGear" key' => [[], 'Configuration item "includeRetiredGear" is required for GearStatsWidget.'];
        yield 'invalid "includeRetiredGear" key' => [['includeRetiredGear' => 'lol'], 'Configuration item "includeRetiredGear" must be a boolean.'];
    }

    protected function setUp(): void
    {
        $this->widget = $this->getContainer()->get(GearStatsWidget::class);
    }
}
