<?php

namespace App\Tests\Domain\Activity\SportType;

use App\Domain\Activity\SportType\SportType;
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
            $snapshot[$sportType->value] = $sportType->getTemplateName();
        }
        $this->assertMatchesJsonSnapshot($snapshot);
    }

    public function testGetVelocityDisplayPreference(): void
    {
        $snapshot = [];
        foreach (SportType::cases() as $sportType) {
            $snapshot[$sportType->value] = $sportType->getVelocityDisplayPreference()::class;
        }
        $this->assertMatchesJsonSnapshot($snapshot);
    }

    public function testGetTranslations(): void
    {
        $snapshot = [];
        foreach (SportType::cases() as $sportType) {
            $snapshot[$sportType->value] = $sportType->trans($this->getContainer()->get(TranslatorInterface::class));
        }
        $this->assertMatchesJsonSnapshot($snapshot);
    }
}
