<?php

namespace App\Tests\Infrastructure\ValueObject\Geography;

use App\Infrastructure\ValueObject\Geography\ZoomLevel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ZoomLevelTest extends TestCase
{
    #[DataProvider('provideValidZoomLevels')]
    public function testItShouldCreateFromValidValue(int $value): void
    {
        $zoomLevel = ZoomLevel::fromInt($value);
        $this->assertSame($value, $zoomLevel->getValue());
    }

    /**
     * @return array<string, array{int}>
     */
    public static function provideValidZoomLevels(): array
    {
        return [
            'min' => [1],
            'mid' => [10],
            'max' => [18],
        ];
    }

    #[DataProvider('provideInvalidZoomLevels')]
    public function testItShouldThrowWhenInvalidZoomLevel(int $value): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ZoomLevel::fromInt($value);
    }

    /**
     * @return array<string, array{int}>
     */
    public static function provideInvalidZoomLevels(): array
    {
        return [
            'zero' => [0],
            'below min' => [-1],
            'above max' => [19],
        ];
    }

    public function testFromOptionalIntReturnsNullForNull(): void
    {
        $this->assertNull(ZoomLevel::fromOptionalInt(null));
    }

    public function testFromOptionalIntReturnsInstanceForValidValue(): void
    {
        $zoomLevel = ZoomLevel::fromOptionalInt(12);
        $this->assertInstanceOf(ZoomLevel::class, $zoomLevel);
        $this->assertSame(12, $zoomLevel->getValue());
    }
}
