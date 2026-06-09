<?php

declare(strict_types=1);

namespace App\Tests\Domain\Import\FileParser\Fit;

use App\Domain\Import\FileParser\Fit\FitProduct;
use App\Infrastructure\Serialization\Json;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

class FitProductTest extends TestCase
{
    use MatchesSnapshots;

    public function testItResolvesEveryKnownProduct(): void
    {
        $reflection = new \ReflectionClass(FitProduct::class);
        /** @var array<int, string> $garmin */
        $garmin = $reflection->getConstant('GARMIN');
        /** @var array<int, string> $favero */
        $favero = $reflection->getConstant('FAVERO');

        $names = ['garmin' => [], 'favero' => []];
        foreach (array_keys($garmin) as $productId) {
            $names['garmin'][$productId] = FitProduct::name(1, $productId);
        }
        foreach (array_keys($favero) as $productId) {
            $names['favero'][$productId] = FitProduct::name(263, $productId);
        }

        $this->assertMatchesJsonSnapshot(Json::encode($names));
    }

    public function testItSupportsOnlyGarminFamilyAndFavero(): void
    {
        foreach ([1, 13, 15, 89, 263] as $manufacturerId) {
            $this->assertTrue(FitProduct::supports($manufacturerId));
        }

        foreach ([0, 23, 32, 123, 294] as $manufacturerId) {
            $this->assertFalse(FitProduct::supports($manufacturerId));
        }
    }

    public function testItReturnsNullForUnsupportedManufacturer(): void
    {
        // Suunto (23) has no product enum, even for a product id we would otherwise know.
        $this->assertNull(FitProduct::name(23, 3121));
    }

    public function testItReturnsNullForUnknownProductId(): void
    {
        $this->assertNull(FitProduct::name(1, 99999999));
    }
}
