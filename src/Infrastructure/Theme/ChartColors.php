<?php

declare(strict_types=1);

namespace App\Infrastructure\Theme;

use App\Domain\Activity\SportType\SportType;
use App\Domain\Gear\GearId;
use Composer\Autoload\ClassLoader;

final readonly class ChartColors
{
    /**
     * @return string[]
     */
    public static function default(): array
    {
        return ['#5470c6', '#91cc75', '#fac858', '#ee6666', '#73c0de', '#3ba272', '#fc8452', '#9a60b4', '#ea7ccc'];
    }

    public static function getColorForSportType(SportType $sportType): string
    {
        $config = require self::getConfigFilePath();

        return $config['sportType'][$sportType->value] ?? throw new \RuntimeException(sprintf('color for sportType "%s" not found', $sportType->value));
    }

    public static function getColorForGear(GearId $gearId): string
    {
        $config = require self::getConfigFilePath();

        return $config['gear'][(string) $gearId] ?? throw new \RuntimeException(sprintf('color for gear "%s" not found', $gearId));
    }

    private static function getConfigFilePath(): string
    {
        static $path = null;
        if (null !== $path) {
            return $path;
        }

        $reflection = new \ReflectionClass(ClassLoader::class);
        /* @phpstan-ignore argument.type */
        $root = dirname($reflection->getFileName(), 2).'/../';
        if (($_ENV['APP_ENV'] ?? 'dev') === 'test') {
            $path = realpath($root).'/tests/chart-colors.php';

            return $path;
        }

        $path = realpath($root).'/config/chart-colors.php';

        return $path;
    }
}
