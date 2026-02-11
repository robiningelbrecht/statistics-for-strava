<?php

namespace App\Tests\Domain\Dashboard\Widget\TrainingLoad;

use App\Domain\Dashboard\Widget\TrainingLoad\AcRatioStatus;
use App\Tests\ContainerTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Contracts\Translation\TranslatorInterface;

class AcRatioStatusTest extends ContainerTestCase
{
    use MatchesSnapshots;

    #[DataProvider(methodName: 'fromFloatProvider')]
    public function testFromFloat(float $value, AcRatioStatus $expected): void
    {
        self::assertSame($expected, AcRatioStatus::fromFloat($value));

        $this->assertMatchesJsonSnapshot([
            'range' => $expected->getRange(),
            'textColor' => $expected->getTextColor(),
            'pillColors' => $expected->getPillColors(),
        ]);
    }

    public function testGetTranslations(): void
    {
        $snapshot = [];
        foreach (AcRatioStatus::cases() as $acRatioStatus) {
            $snapshot[$acRatioStatus->name] = $acRatioStatus->trans($this->getContainer()->get(TranslatorInterface::class));
        }
        $this->assertMatchesJsonSnapshot($snapshot);
    }

    public function testGetDescriptionsTranslations(): void
    {
        $snapshot = [];
        foreach (AcRatioStatus::cases() as $acRatioStatus) {
            $snapshot[$acRatioStatus->name] = $acRatioStatus->transDescription($this->getContainer()->get(TranslatorInterface::class));
        }
        $this->assertMatchesJsonSnapshot($snapshot);
    }

    public static function fromFloatProvider(): iterable
    {
        yield 'high risk' => [1.31, AcRatioStatus::HIGH_RISK];
        yield 'low training load' => [0.79, AcRatioStatus::LOW_TRAINING_LOAD];
        yield 'lower boundary low risk' => [0.8, AcRatioStatus::LOW_RISK];
        yield 'upper boundary low risk' => [1.3, AcRatioStatus::LOW_RISK];
        yield 'middle low risk' => [1.0, AcRatioStatus::LOW_RISK];
    }
}
