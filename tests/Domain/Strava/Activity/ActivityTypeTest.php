<?php

namespace App\Tests\Domain\Strava\Activity;

use App\Domain\Strava\Activity\ActivityType;
use App\Tests\ContainerTestCase;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Contracts\Translation\TranslatorInterface;

class ActivityTypeTest extends ContainerTestCase
{
    use MatchesSnapshots;

    public function testGetSportTypes(): void
    {
        $snapshot = [];
        foreach (ActivityType::cases() as $activityType) {
            $snapshot[] = $activityType->getSportTypes();
        }
        $this->assertMatchesJsonSnapshot($snapshot);
    }

    public function testGetColor(): void
    {
        $snapshot = [];
        foreach (ActivityType::cases() as $activityType) {
            $snapshot[] = $activityType->getColor();
        }
        $this->assertMatchesJsonSnapshot($snapshot);
    }

    public function testGetTranslations(): void
    {
        $snapshot = [];
        foreach (ActivityType::cases() as $activityType) {
            $snapshot[] = $activityType->trans($this->getContainer()->get(TranslatorInterface::class));
        }
        $this->assertMatchesJsonSnapshot($snapshot);
    }
}
