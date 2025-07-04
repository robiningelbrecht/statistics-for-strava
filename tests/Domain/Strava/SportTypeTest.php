<?php

namespace App\Tests\Domain\Strava;

use App\Domain\Strava\Activity\SportType\SportType;
use App\Tests\ContainerTestCase;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Contracts\Translation\TranslatorInterface;

class SportTypeTest extends ContainerTestCase
{
    use MatchesSnapshots;

    public function testGetTemplateName(): void
    {
        $snapshot = [];
        foreach (SportType::cases() as $sportType) {
            $snapshot[] = $sportType->getTemplateName();
        }
        $this->assertMatchesJsonSnapshot($snapshot);
    }

    public function testGetTranslations(): void
    {
        $snapshot = [];
        foreach (SportType::cases() as $sportType) {
            $snapshot[] = $sportType->trans($this->getContainer()->get(TranslatorInterface::class));
        }
        $this->assertMatchesJsonSnapshot($snapshot);
    }
}
