<?php

namespace App\Tests\Domain\Strava\Athlete\HeartRateZone;

use App\Domain\Strava\Activity\SportType\SportType;
use App\Domain\Strava\Athlete\HeartRateZone\HeartRateZoneConfiguration;
use App\Domain\Strava\Athlete\HeartRateZone\HeartRateZoneMode;
use App\Domain\Strava\Athlete\HeartRateZone\HeartRateZones;
use App\Domain\Strava\Athlete\HeartRateZone\InvalidHeartZoneConfiguration;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Yaml\Yaml;

class HeartRateZoneConfigurationTest extends TestCase
{
    use MatchesSnapshots;

    #[DataProvider(methodName: 'provideValidConfig')]
    public function testGetHeartRateZonesFor(array $config, HeartRateZoneMode $expectedMode, HeartRateZones $expectedHeartRateZones, SportType $sportType, SerializableDateTime $on): void
    {
        $config = HeartRateZoneConfiguration::fromArray($config);

        $this->assertEquals(
            $expectedMode,
            $config->getMode(),
        );

        $this->assertEquals(
            $expectedHeartRateZones,
            $config->getHeartRateZonesFor($sportType, $on),
        );
    }

    public function testFromArrayWhenEmptyNeedsToUseDefaults(): void
    {
        $this->assertEquals(
            HeartRateZoneConfiguration::fromArray([
                'mode' => 'relative',
                'default' => [
                    'zone1' => [
                        'from' => 0,
                        'to' => 60,
                    ],
                    'zone2' => [
                        'from' => 61,
                        'to' => 70,
                    ],
                    'zone3' => [
                        'from' => 71,
                        'to' => 80,
                    ],
                    'zone4' => [
                        'from' => 81,
                        'to' => 90,
                    ],
                    'zone5' => [
                        'from' => 91,
                        'to' => null,
                    ],
                ],
            ]),
            HeartRateZoneConfiguration::fromArray([])
        );
    }

    #[DataProvider(methodName: 'provideInvalidConfig')]
    public function testFromYmlStringItShouldThrow(array $yml, string $expectedException): void
    {
        $this->expectExceptionObject(new InvalidHeartZoneConfiguration($expectedException));
        HeartRateZoneConfiguration::fromArray($yml);
    }

    public static function provideValidConfig(): iterable
    {
        $yml = self::getValidYml();

        yield 'should use sportType and On' => [
            $yml,
            HeartRateZoneMode::RELATIVE,
            HeartRateZones::fromScalarValues(HeartRateZoneMode::RELATIVE, [
                'zone1' => [
                    'from' => 0,
                    'to' => 60,
                ],
                'zone2' => [
                    'from' => 61,
                    'to' => 70,
                ],
                'zone3' => [
                    'from' => 71,
                    'to' => 80,
                ],
                'zone4' => [
                    'from' => 81,
                    'to' => 82,
                ],
                'zone5' => [
                    'from' => 83,
                    'to' => null,
                ],
            ]),
            SportType::GRAVEL_RIDE,
            SerializableDateTime::fromString('2025-01-01'),
        ];

        $yml = self::getValidYml();
        $yml['mode'] = 'absolute';

        yield 'should use sportType only' => [
            $yml,
            HeartRateZoneMode::ABSOLUTE,
            HeartRateZones::fromScalarValues(HeartRateZoneMode::ABSOLUTE, [
                'zone1' => [
                    'from' => 0,
                    'to' => 60,
                ],
                'zone2' => [
                    'from' => 61,
                    'to' => 70,
                ],
                'zone3' => [
                    'from' => 71,
                    'to' => 80,
                ],
                'zone4' => [
                    'from' => 81,
                    'to' => 83,
                ],
                'zone5' => [
                    'from' => 84,
                    'to' => null,
                ],
            ]),
            SportType::GRAVEL_RIDE,
            SerializableDateTime::fromString('2024-01-01'),
        ];

        unset($yml['sportTypes']['GravelRide']['dateRanges']);
        yield 'should use sportType only because dateRanges are not set' => [
            $yml,
            HeartRateZoneMode::ABSOLUTE,
            HeartRateZones::fromScalarValues(HeartRateZoneMode::ABSOLUTE, [
                'zone1' => [
                    'from' => 0,
                    'to' => 60,
                ],
                'zone2' => [
                    'from' => 61,
                    'to' => 70,
                ],
                'zone3' => [
                    'from' => 71,
                    'to' => 80,
                ],
                'zone4' => [
                    'from' => 81,
                    'to' => 83,
                ],
                'zone5' => [
                    'from' => 84,
                    'to' => null,
                ],
            ]),
            SportType::GRAVEL_RIDE,
            SerializableDateTime::fromString('2024-01-01'),
        ];

        $yml = self::getValidYml();
        $yml['mode'] = 'absolute';
        yield 'should use "on" only' => [
            $yml,
            HeartRateZoneMode::ABSOLUTE,
            HeartRateZones::fromScalarValues(HeartRateZoneMode::ABSOLUTE, [
                'zone1' => [
                    'from' => 0,
                    'to' => 60,
                ],
                'zone2' => [
                    'from' => 61,
                    'to' => 70,
                ],
                'zone3' => [
                    'from' => 71,
                    'to' => 80,
                ],
                'zone4' => [
                    'from' => 81,
                    'to' => 85,
                ],
                'zone5' => [
                    'from' => 86,
                    'to' => null,
                ],
            ]),
            SportType::WALK,
            SerializableDateTime::fromString('2025-01-01'),
        ];

        yield 'should use default' => [
            $yml,
            HeartRateZoneMode::ABSOLUTE,
            HeartRateZones::fromScalarValues(HeartRateZoneMode::ABSOLUTE, [
                'zone1' => [
                    'from' => 0,
                    'to' => 60,
                ],
                'zone2' => [
                    'from' => 61,
                    'to' => 70,
                ],
                'zone3' => [
                    'from' => 71,
                    'to' => 80,
                ],
                'zone4' => [
                    'from' => 81,
                    'to' => 90,
                ],
                'zone5' => [
                    'from' => 91,
                    'to' => null,
                ],
            ]),
            SportType::WALK,
            SerializableDateTime::fromString('2024-01-01'),
        ];

        unset($yml['dateRanges']);
        yield 'should use default because dateRanges are not set' => [
            $yml,
            HeartRateZoneMode::ABSOLUTE,
            HeartRateZones::fromScalarValues(HeartRateZoneMode::ABSOLUTE, [
                'zone1' => [
                    'from' => 0,
                    'to' => 60,
                ],
                'zone2' => [
                    'from' => 61,
                    'to' => 70,
                ],
                'zone3' => [
                    'from' => 71,
                    'to' => 80,
                ],
                'zone4' => [
                    'from' => 81,
                    'to' => 90,
                ],
                'zone5' => [
                    'from' => 91,
                    'to' => null,
                ],
            ]),
            SportType::WALK,
            SerializableDateTime::fromString('2024-01-01'),
        ];
    }

    public static function provideInvalidConfig(): iterable
    {
        $yml = self::getValidYml();
        unset($yml['mode']);
        yield 'missing "mode" key' => [$yml, '"mode" property is required'];

        $yml = self::getValidYml();
        unset($yml['default']);
        yield 'missing "default" key' => [$yml, '"default" property is required'];

        $yml = self::getValidYml();
        $yml['mode'] = 'lol';
        yield 'invalid "mode" key' => [$yml, '"lol" is not a valid mode'];

        $yml = self::getValidYml();
        $yml['default'] = 'lol';
        yield 'invalid "default" key' => [$yml, '"default" property must be an array'];

        $yml = self::getValidYml();
        $yml['dateRanges'] = 'lol';
        yield 'invalid "dateRanges" key' => [$yml, '"dateRanges" property must be an array'];

        $yml = self::getValidYml();
        $yml['sportTypes'] = 'lol';
        yield 'invalid "sportTypes" key' => [$yml, '"sportTypes" property must be an array'];

        $yml = self::getValidYml();
        $yml['sportTypes']['lol'] = [];
        yield 'invalid "sportType"' => [$yml, '"lol" is not a valid sport type'];

        $yml = self::getValidYml();
        unset($yml['sportTypes']['GravelRide']['default']);
        yield 'missing "default" key for sportType' => [$yml, '"default" property is required for sportType GravelRide'];

        $yml = self::getValidYml();
        $yml['sportTypes']['GravelRide']['default'] = 'lol';
        yield 'invalid "default" key for sportType' => [$yml, '"default" property must be an array for sportType GravelRide'];

        $yml = self::getValidYml();
        $yml['sportTypes']['GravelRide']['dateRanges'] = 'lol';
        yield 'invalid "dateRanges" key for sportType' => [$yml, '"dateRanges" property must be an array for sportType GravelRide'];

        $yml = self::getValidYml();
        $yml['dateRanges']['invalidDate'] = [];
        yield 'invalid "dateRanges" date' => [$yml, 'Invalid date "invalidDate" set for athlete heartRateZone'];

        $yml = self::getValidYml();
        unset($yml['default']['zone1']);
        yield 'missing "zone1"' => [$yml, '"zone1" property is required for each range of heart zones'];

        $yml = self::getValidYml();
        $yml['default']['zone1']['from'] = 1;
        yield 'invalid "zone1" from value' => [$yml, 'zone1 "from" value needs to be 0, got 1'];

        $yml = self::getValidYml();
        $yml['default']['zone5']['to'] = 1;
        yield 'invalid "zone5" to value' => [$yml, 'zone5 "to" value needs to be null, got 1'];

        $yml = self::getValidYml();
        $yml['default']['zone2']['from'] = 'lol';
        yield 'invalid "zone" from value' => [$yml, 'zone2 "from" value needs to a positive integer, got lol'];

        $yml = self::getValidYml();
        $yml['default']['zone2']['from'] = -1;
        yield 'invalid "zone" from value case 2' => [$yml, 'zone2 "from" value needs to a positive integer, got -1'];

        $yml = self::getValidYml();
        $yml['default']['zone2']['to'] = 'lol';
        yield 'invalid "zone" to value' => [$yml, 'zone2 "to" value needs to a valid integer, got lol'];

        $yml = self::getValidYml();
        $yml['default']['zone2']['to'] = 100;
        yield 'invalid "zone" to value case 2' => [$yml, 'zone2 "to" value cannot be higher than 99, got 100'];

        $yml = self::getValidYml();
        $yml['default']['zone2']['from'] = 70;
        yield 'gap detected' => [$yml, 'Gap detected before zone2: expected "from" to be 61, got 70'];

        $yml = self::getValidYml();
        $yml['default']['zone2']['to'] = 50;
        yield 'from bigger than to' => [$yml, 'zone2 has "from" (61) greater than "to" (50), which is invalid'];
    }

    private static function getValidYml(): array
    {
        return Yaml::parse(<<<YML
mode: relative
default:
  zone2:
    from: 61
    to: 70
  zone1:
    from: 0
    to: 60
  zone3:
    from: 71
    to: 80
  zone4:
    from: 81
    to: 90
  zone5:
    from: 91
    to: null
dateRanges:
  "2025-01-01":
    zone1:
      from: 0
      to: 60
    zone2:
      from: 61
      to: 70
    zone3:
      from: 71
      to: 80
    zone4:
      from: 81
      to: 85
    zone5:
      from: 86
      to: null
  "2024-11-08":
    zone1:
      from: 0
      to: 60
    zone2:
      from: 61
      to: 70
    zone3:
      from: 71
      to: 80
    zone4:
      from: 81
      to: 84
    zone5:
      from: 85
      to: null
sportTypes:
  GravelRide:
    default:
      zone1:
        from: 0
        to: 60
      zone2:
        from: 61
        to: 70
      zone3:
        from: 71
        to: 80
      zone4:
        from: 81
        to: 83
      zone5:
        from: 84
        to: null
    dateRanges:
      "2025-01-01":
        zone1:
          from: 0
          to: 60
        zone2:
          from: 61
          to: 70
        zone3:
          from: 71
          to: 80
        zone4:
          from: 81
          to: 82
        zone5:
          from: 83
          to: null
      "2024-11-08":
        zone1:
          from: 0
          to: 60
        zone2:
          from: 61
          to: 70
        zone3:
          from: 71
          to: 80
        zone4:
          from: 81
          to: 87
        zone5:
          from: 88
          to: null
YML
        );
    }
}
