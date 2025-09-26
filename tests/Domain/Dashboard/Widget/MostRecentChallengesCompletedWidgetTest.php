<?php

namespace App\Tests\Domain\Dashboard\Widget;

use App\Domain\Dashboard\InvalidDashboardLayout;
use App\Domain\Dashboard\Widget\MostRecentChallengesCompletedWidget;
use App\Domain\Dashboard\Widget\WidgetConfiguration;
use App\Tests\ContainerTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class MostRecentChallengesCompletedWidgetTest extends ContainerTestCase
{
    private MostRecentChallengesCompletedWidget $widget;

    #[DataProvider(methodName: 'provideInvalidConfig')]
    public function testGuardValidConfigurationItShouldThrow(WidgetConfiguration $config, string $expectedException): void
    {
        $this->expectExceptionObject(new InvalidDashboardLayout($expectedException));
        $this->widget->guardValidConfiguration($config);
    }

    public static function provideInvalidConfig(): iterable
    {
        yield 'missing "numberOfChallengesToDisplay" key' => [WidgetConfiguration::empty(), 'Configuration item "numberOfChallengesToDisplay" is required for MostRecentChallengesCompletedWidget.'];
        $config = WidgetConfiguration::empty()
            ->add('numberOfChallengesToDisplay', 'lol');
        yield 'invalid "numberOfChallengesToDisplay" key' => [$config, 'Configuration item "numberOfChallengesToDisplay" must be an integer.'];
        $config = WidgetConfiguration::empty()
            ->add('numberOfChallengesToDisplay', 0);
        yield 'too small "numberOfChallengesToDisplay" key' => [$config, 'Configuration item "numberOfChallengesToDisplay" must be set to a value of 1 or greater.'];
    }

    protected function setUp(): void
    {
        $this->widget = $this->getContainer()->get(MostRecentChallengesCompletedWidget::class);
    }
}
