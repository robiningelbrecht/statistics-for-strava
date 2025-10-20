<?php

namespace App\Tests\Domain\Dashboard\Widget;

use App\Domain\Dashboard\InvalidDashboardLayout;
use App\Domain\Dashboard\Widget\GearStatsWidget;
use App\Domain\Dashboard\Widget\WidgetConfiguration;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use App\Tests\ProvideTestData;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Snapshots\MatchesSnapshots;

class GearStatsWidgetTest extends ContainerTestCase
{
    use ProvideTestData;
    use MatchesSnapshots;

    private GearStatsWidget $widget;

    public function testRender(): void
    {
        $this->provideFullTestSet();

        $config = WidgetConfiguration::empty()
            ->add('includeRetiredGear', false);

        $render = $this->widget->render(
            now: SerializableDateTime::fromString('2025-10-16'),
            configuration: $config
        );
        $this->assertMatchesHtmlSnapshot($render);
    }

    #[DataProvider(methodName: 'provideInvalidConfig')]
    public function testGuardValidConfigurationItShouldThrow(WidgetConfiguration $config, string $expectedException): void
    {
        $this->expectExceptionObject(new InvalidDashboardLayout($expectedException));
        $this->widget->guardValidConfiguration($config);
    }

    public static function provideInvalidConfig(): iterable
    {
        yield 'missing "includeRetiredGear" key' => [WidgetConfiguration::empty(), 'Configuration item "includeRetiredGear" is required for GearStatsWidget.'];
        yield 'invalid "includeRetiredGear" key' => [WidgetConfiguration::empty()->add('includeRetiredGear', 'lol'), 'Configuration item "includeRetiredGear" must be a boolean.'];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->widget = $this->getContainer()->get(GearStatsWidget::class);
    }
}
