<?php

namespace App\Tests\Domain\Dashboard\Widget;

use App\Domain\Dashboard\DashboardLayout;
use App\Domain\Dashboard\Widget\Widgets;
use App\Tests\ContainerTestCase;
use App\Tests\Infrastructure\Time\Clock\PausedClock;

class WidgetsTest extends ContainerTestCase
{
    public function testWhenWidgetDoesNotExists(): void
    {
        $widgets = new Widgets(
            widgets: [],
            dashboardLayout: DashboardLayout::fromArray([
                ['widget' => 'invalid', 'width' => 100, 'enabled' => true],
            ]),
            clock: PausedClock::fromString('2026-01-09')
        );

        $this->expectExceptionObject(new \InvalidArgumentException('Dashboard widget "invalid" does not exists.'));
        $widgets->getIterator();
    }

    public function testWhenWidgetHasBeenRemoved(): void
    {
        $widgets = new Widgets(
            widgets: [],
            dashboardLayout: DashboardLayout::fromArray([
                ['widget' => 'bestEfforts', 'width' => 100, 'enabled' => true],
            ]),
            clock: PausedClock::fromString('2026-01-09')
        );

        $this->assertCount(
            0,
            $widgets->getIterator(),
        );
    }
}
