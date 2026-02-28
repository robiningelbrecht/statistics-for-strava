<?php

namespace App\Tests\Domain\Gear\RecordingDevice;

use App\Domain\Gear\RecordingDevice\InvalidRecordingDevicesConfig;
use App\Domain\Gear\RecordingDevice\RecordingDevicesConfig;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

class RecordingDevicesConfigTest extends TestCase
{
    use MatchesSnapshots;

    #[DataProvider(methodName: 'provideInvalidConfig')]
    public function testFromYmlStringItShouldThrow(array $config, string $expectedException): void
    {
        $this->expectExceptionObject(new InvalidRecordingDevicesConfig($expectedException));
        RecordingDevicesConfig::fromArray($config);
    }

    public static function provideInvalidConfig(): iterable
    {
        $config = self::getValidConfig();
        unset($config[0]['gearId']);
        yield 'missing "gearId" key' => [$config, '"gearId" property is required for each recording device'];

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

    public function testGetPurchasePriceForUnknownDevice(): void
    {
        $config = RecordingDevicesConfig::fromArray(self::getValidConfig());

        $this->assertNull($config->getPurchasePrice('unknown-device'));
    }

    public function testGetPurchasePriceForDeviceWithoutPrice(): void
    {
        $config = RecordingDevicesConfig::fromArray(self::getValidConfig());

        $this->assertNull($config->getPurchasePrice('le-id-not'));
    }

    public function testGetPurchasePriceForDeviceWithPrice(): void
    {
        $config = RecordingDevicesConfig::fromArray(self::getValidConfig());

        $this->assertEquals(
            new Money(1000, new Currency('EUR')),
            $config->getPurchasePrice('le-id')
        );
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
