<?php

namespace App\Tests\Domain\Gear\ImportedGear;

use App\Domain\Gear\GearId;
use App\Domain\Gear\ImportedGear\ImportedGearConfig;
use App\Domain\Gear\ImportedGear\InvalidImportedGearConfig;
use Money\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

class ImportedGearConfigTest extends TestCase
{
    use MatchesSnapshots;

    public function testEnrichGearWithCustomData(): void
    {
        $config = ImportedGearConfig::fromArray(self::getValidConfig());

        $gear = ImportedGearBuilder::fromDefaults()
            ->withGearId(GearId::fromUnprefixed('le-id'))
            ->build();
        $config->enrichGearWithCustomData($gear);

        $this->assertEquals(
            Money::EUR('1000'),
            $gear->getPurchasePrice(),
        );
        $this->assertNull(ImportedGearBuilder::fromDefaults()->build()->getPurchasePrice());

        $gear = ImportedGearBuilder::fromDefaults()
            ->withGearId(GearId::fromUnprefixed('le-id-not'))
            ->build();
        $config->enrichGearWithCustomData($gear);
        $this->assertNull($gear->getPurchasePrice());
    }

    #[DataProvider(methodName: 'provideInvalidConfig')]
    public function testFromYmlStringItShouldThrow(array $config, string $expectedException): void
    {
        $this->expectExceptionObject(new InvalidImportedGearConfig($expectedException));
        ImportedGearConfig::fromArray($config);
    }

    public static function provideInvalidConfig(): iterable
    {
        $config = self::getValidConfig();
        unset($config[0]['gearId']);
        yield 'missing "gearId" key' => [$config, '"gearId" property is required for each gear'];

        $config = self::getValidConfig();
        unset($config[1]['purchasePrice']['amountInCents']);
        yield 'missing "amountInCents" key' => [$config, '"purchasePrice.amountInCents" property must be a numeric value'];

        $config = self::getValidConfig();
        $config[1]['purchasePrice']['amountInCents'] = 'lol';
        yield 'invalid "amountInCents" key' => [$config, '"purchasePrice.amountInCents" property must be a numeric value'];

        $config = self::getValidConfig();
        unset($config[1]['purchasePrice']['currency']);
        yield 'missing "currency" key' => [$config, '"purchasePrice.currency" property is required'];
    }

    private static function getValidConfig(): array
    {
        return [
            [
                'gearId' => 'le-id-not',
            ],
            [
                'gearId' => 'le-id',
                'purchasePrice' => [
                    'amountInCents' => 1000,
                    'currency' => 'EUR',
                ],
            ],
        ];
    }
}
