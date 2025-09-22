<?php

namespace App\Tests\Domain\Dashboard\Widget;

use App\Domain\Dashboard\InvalidDashboardLayout;
use App\Domain\Dashboard\Widget\MostRecentActivitiesWidget;
use App\Tests\ContainerTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class MostRecentActivitiesWidgetTest extends ContainerTestCase
{
    private MostRecentActivitiesWidget $widget;

    #[DataProvider(methodName: 'provideInvalidConfig')]
    public function testGuardValidConfigurationItShouldThrow(array $config, string $expectedException): void
    {
        $this->expectExceptionObject(new InvalidDashboardLayout($expectedException));
        $this->widget->guardValidConfiguration($config);
    }

    public static function provideInvalidConfig(): iterable
    {
        yield 'missing "numberOfActivitiesToDisplay" key' => [[], 'Configuration item "numberOfActivitiesToDisplay" is required for MostRecentActivitiesWidget.'];
        yield 'invalid "numberOfActivitiesToDisplay" key' => [['numberOfActivitiesToDisplay' => 'lol'], 'Configuration item "numberOfActivitiesToDisplay" must be an integer.'];
        yield 'too small "numberOfActivitiesToDisplay" key' => [['numberOfActivitiesToDisplay' => 0], 'Configuration item "numberOfActivitiesToDisplay" must be set to a value of 1 or greater.'];
    }

    protected function setUp(): void
    {
        $this->widget = $this->getContainer()->get(MostRecentActivitiesWidget::class);
    }
}
