<?php

namespace App\Tests\BuildApp\BuildDashboardHtml\Layout\Widget;

use App\BuildApp\BuildDashboardHtml\Layout\Widget\WidgetName;
use PHPUnit\Framework\TestCase;

class WidgetNameTest extends TestCase
{
    public function testActivityIntensityBackwardsCompatibility(): void
    {
        $this->assertEquals(
            'activityGrid',
            (string) WidgetName::fromConfigValue('activityIntensity')
        );
    }
}
