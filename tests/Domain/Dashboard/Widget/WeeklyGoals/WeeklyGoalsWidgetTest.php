<?php

namespace App\Tests\Domain\Dashboard\Widget\WeeklyGoals;

use App\Domain\Dashboard\Widget\WeeklyGoals\WeeklyGoalsWidget;
use App\Domain\Dashboard\Widget\WidgetConfiguration;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use App\Tests\ProvideTestData;
use Spatie\Snapshots\MatchesSnapshots;

class WeeklyGoalsWidgetTest extends ContainerTestCase
{
    use ProvideTestData;
    use MatchesSnapshots;

    private WeeklyGoalsWidget $widget;

    public function testGetDefaultConfiguration(): void
    {
        $this->assertEquals(
            WidgetConfiguration::empty()
                ->add('goals', []),
            $this->widget->getDefaultConfiguration()
        );
    }

    public function testRender(): void
    {
        $this->provideFullTestSet();

        $config = WidgetConfiguration::empty()
            ->add('goals', [
                ['label' => 'Cycling',  'enabled' => true, 'type' => 'distance', 'unit' => 'km', 'goal' => 200,  'sportTypesToInclude' => ['Ride', 'MountainBikeRide', 'GravelRide', 'VirtualRide']],
                ['label' => 'Cycling',  'enabled' => true, 'type' => 'elevation', 'unit' => 'm', 'goal' => 1000,  'sportTypesToInclude' => ['Ride', 'MountainBikeRide', 'GravelRide', 'VirtualRide']],
                ['label' => 'Cycling',  'enabled' => true, 'type' => 'movingTime', 'unit' => 'hour', 'goal' => 2,  'sportTypesToInclude' => ['Ride', 'MountainBikeRide', 'GravelRide', 'VirtualRide']],
                ['label' => 'Running',  'enabled' => true, 'type' => 'movingTime', 'unit' => 'hour', 'goal' => 2,  'sportTypesToInclude' => ['Run']],
                ['label' => 'Running',  'enabled' => false, 'type' => 'elevation', 'unit' => 'm', 'goal' => 2,  'sportTypesToInclude' => ['Run']],
            ]);

        $render = $this->widget->render(
            now: SerializableDateTime::fromString('2025-10-16'),
            configuration: $config
        );
        $this->assertMatchesHtmlSnapshot($render);
    }

    public function testRenderWhenNoGoals(): void
    {
        $render = $this->widget->render(
            now: SerializableDateTime::fromString('2025-10-16'),
            configuration: WidgetConfiguration::empty()
        );
        $this->assertNull($render);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->widget = $this->getContainer()->get(WeeklyGoalsWidget::class);
    }
}
