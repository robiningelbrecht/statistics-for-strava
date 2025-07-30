<?php

namespace App\Tests\Domain\App\BuildDashboardHtml\Layout;

use App\Domain\App\BuildDashboardHtml\Layout\DashboardLayout;
use App\Domain\App\BuildDashboardHtml\Layout\InvalidDashboardLayout;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

class DashboardLayoutTest extends TestCase
{
    public function testFromArrayWhenEmpty(): void
    {
        $this->assertEquals(
            DashboardLayout::fromArray(null),
            DashboardLayout::fromArray([
                ['widget' => 'mostRecentActivities', 'width' => 66, 'enabled' => true],
                ['widget' => 'introText', 'width' => 33, 'enabled' => true],
                ['widget' => 'weeklyStats', 'width' => 100, 'enabled' => true],
                ['widget' => 'peakPowerOutputs', 'width' => 50, 'enabled' => true],
                ['widget' => 'heartRateZones', 'width' => 50, 'enabled' => true],
                ['widget' => 'activityIntensity', 'width' => 100, 'enabled' => true],
                ['widget' => 'monthlyStats', 'width' => 100, 'enabled' => true],
                ['widget' => 'trainingLoad', 'width' => 100, 'enabled' => true],
                ['widget' => 'weekdayStats', 'width' => 50, 'enabled' => true],
                ['widget' => 'dayTimeStats', 'width' => 50, 'enabled' => true],
                ['widget' => 'distanceBreakdown', 'width' => 100, 'enabled' => true],
                ['widget' => 'bestEfforts', 'width' => 100, 'enabled' => true],
                ['widget' => 'yearlyDistances', 'width' => 100, 'enabled' => true],
                ['widget' => 'challengeConsistency', 'width' => 50, 'enabled' => true],
                ['widget' => 'ftpHistory', 'width' => 50, 'enabled' => true],
            ])
        );
    }

    #[DataProvider(methodName: 'provideInvalidConfig')]
    public function testFromYmlStringItShouldThrow(array $yml, string $expectedException): void
    {
        $this->expectExceptionObject(new InvalidDashboardLayout($expectedException));
        DashboardLayout::fromArray($yml);
    }

    public static function provideInvalidConfig(): iterable
    {
        $yml = self::getValidYml();
        unset($yml[0]['widget']);
        yield 'missing "widget" key' => [$yml, '"widget" property is required'];

        $yml = self::getValidYml();
        unset($yml[0]['width']);
        yield 'missing "width" key' => [$yml, '"width" property is required'];

        $yml = self::getValidYml();
        unset($yml[0]['enabled']);
        yield 'missing "enabled" key' => [$yml, '"enabled" property is required'];

        $yml = self::getValidYml();
        $yml[0]['enabled'] = 'test';
        yield 'invalid "enabled" key' => [$yml, '"enabled" property must be a boolean'];

        $yml = self::getValidYml();
        $yml[0]['width'] = '33';
        yield 'invalid "width" key' => [$yml, '"width" property must be a valid integer'];

        $yml = self::getValidYml();
        $yml[0]['width'] = 20;
        yield 'invalid "width" key case 2' => [$yml, '"width" property must be one of [33, 50, 66, 100], found 20'];
    }

    private static function getValidYml(): array
    {
        return Yaml::parse(<<<YML
- {'widget': 'mostRecentActivities', 'width': 66, 'enabled': true}
- {'widget': 'introText', 'width': 33, 'enabled': true}
- {'widget': 'weeklyStats', 'width': 100, 'enabled': true}
- {'widget': 'peakPowerOutputs', 'width': 50, 'enabled': true}
- {'widget': 'heartRateZones', 'width': 50, 'enabled': true}
- {'widget': 'activityIntensity', 'width': 100, 'enabled': true}
- {'widget': 'trainingLoad', 'width': 100, 'enabled': true}
- {'widget': 'weekdayStats', 'width': 50, 'enabled': true}
- {'widget': 'dayTimeStats', 'width': 50, 'enabled': true}
- {'widget': 'distanceBreakdown', 'width': 100, 'enabled': true}
- {'widget': 'bestEfforts', 'width': 100, 'enabled': true}
- {'widget': 'yearlyDistances', 'width': 100, 'enabled': true}
- {'widget': 'challengeConsistency', 'width': 50, 'enabled': true}
- {'widget': 'ftpHistory', 'width': 50, 'enabled': true}
YML
        );
    }
}
