<?php

namespace App\Tests\Infrastructure\Twig;

use App\Domain\Activity\SportType\SportType;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\Twig\SvgsTwigExtension;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

class SvgsTwigExtensionTest extends TestCase
{
    use MatchesSnapshots;

    public function testInvalidSvg(): void
    {
        $this->expectExceptionObject(new \RuntimeException('No svg icon found for "invalid"'));
        new SvgsTwigExtension()->svg('invalid');
    }

    public function testSportTypeSvgs(): void
    {
        $snapshot = [];

        foreach (SportType::cases() as $sportType) {
            $snapshot[$sportType->value] = new SvgsTwigExtension()->svgSportType($sportType);
        }
        $this->assertMatchesJsonSnapshot(Json::encode($snapshot));
    }
}
