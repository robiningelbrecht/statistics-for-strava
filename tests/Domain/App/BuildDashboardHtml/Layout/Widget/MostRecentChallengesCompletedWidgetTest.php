<?php

namespace App\Tests\Domain\App\BuildDashboardHtml\Layout\Widget;

use App\Domain\App\BuildDashboardHtml\Layout\InvalidDashboardLayout;
use App\Domain\App\BuildDashboardHtml\Layout\Widget\MostRecentChallengesCompletedWidget;
use App\Tests\ContainerTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class MostRecentChallengesCompletedWidgetTest extends ContainerTestCase
{
    private MostRecentChallengesCompletedWidget $widget;

    #[DataProvider(methodName: 'provideInvalidConfig')]
    public function testGuardValidConfigurationItShouldThrow(array $config, string $expectedException): void
    {
        $this->expectExceptionObject(new InvalidDashboardLayout($expectedException));
        $this->widget->guardValidConfiguration($config);
    }

    public static function provideInvalidConfig(): iterable
    {
        yield 'missing "numberOfChallengesToDisplay" key' => [[], 'Configuration item "numberOfChallengesToDisplay" is required for MostRecentChallengesCompletedWidget.'];
        yield 'invalid "numberOfChallengesToDisplay" key' => [['numberOfChallengesToDisplay' => 'lol'], 'Configuration item "numberOfChallengesToDisplay" must be an integer.'];
        yield 'too small "numberOfChallengesToDisplay" key' => [['numberOfChallengesToDisplay' => 0], 'Configuration item "numberOfChallengesToDisplay" must be set to a value of 1 or greater.'];
    }

    protected function setUp(): void
    {
        $this->widget = $this->getContainer()->get(MostRecentChallengesCompletedWidget::class);
    }
}
