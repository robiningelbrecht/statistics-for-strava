<?php

namespace App\Tests\Domain\Dashboard\Widget\TrainingLoad;

use App\Domain\Dashboard\Widget\TrainingLoad\TSBStatus;
use App\Tests\ContainerTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Contracts\Translation\TranslatorInterface;

class TSBStatusTest extends ContainerTestCase
{
    use MatchesSnapshots;

    #[DataProvider(methodName: 'fromFloatProvider')]
    public function testFromFloat(float $value, TSBStatus $expected): void
    {
        self::assertSame($expected, TSBStatus::fromFloat($value));

        $this->assertMatchesJsonSnapshot([
            'range' => $expected->getRange(),
            'textColor' => $expected->getTextColor(),
            'pillColors' => $expected->getPillColors(),
        ]);
    }

    public function testGetTranslations(): void
    {
        $snapshot = [];
        foreach (TSBStatus::cases() as $status) {
            $snapshot[$status->name] = $status->trans($this->getContainer()->get(TranslatorInterface::class));
        }
        $this->assertMatchesJsonSnapshot($snapshot);
    }

    public function testGetDescriptionsTranslations(): void
    {
        $snapshot = [];
        foreach (TSBStatus::cases() as $status) {
            $snapshot[$status->name] = $status->transDescription($this->getContainer()->get(TranslatorInterface::class));
        }
        $this->assertMatchesJsonSnapshot($snapshot);
    }

    public static function fromFloatProvider(): iterable
    {
        // POSSIBLE_DETRAINING (> 25)
        yield 'possible detraining' => [25.1, TSBStatus::POSSIBLE_DETRAINING];

        // PEAK_FRESH (10 – 25]
        yield 'peak fresh middle' => [20.0, TSBStatus::PEAK_FRESH];
        yield 'peak fresh upper boundary' => [25.0, TSBStatus::PEAK_FRESH];

        // SLIGHTLY_FRESH (0 – 10]
        yield 'slightly fresh middle' => [5.0, TSBStatus::SLIGHTLY_FRESH];
        yield 'slightly fresh upper boundary' => [10.0, TSBStatus::SLIGHTLY_FRESH];

        // NEUTRAL (-10 – 0]
        yield 'neutral middle' => [-5.0, TSBStatus::NEUTRAL];
        yield 'neutral upper boundary' => [0.0, TSBStatus::NEUTRAL];

        // ACCUMULATED_FATIGUE (-30 – -10]
        yield 'accumulated fatigue middle' => [-20.0, TSBStatus::ACCUMULATED_FATIGUE];
        yield 'accumulated fatigue upper boundary' => [-10.0, TSBStatus::ACCUMULATED_FATIGUE];

        // OVER_FATIGUED (< -30)
        yield 'over fatigued' => [-30.1, TSBStatus::OVER_FATIGUED];
        yield 'over fatigued boundary' => [-30.0, TSBStatus::OVER_FATIGUED];
    }
}
