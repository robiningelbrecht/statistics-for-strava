<?php

namespace App\Tests\Domain\Activity\Image;

use App\Domain\Activity\Image\ImageOrientation;
use PHPUnit\Framework\TestCase;

class ImageOrientationTest extends TestCase
{
    public function testFromWidthAndHeight()
    {
        $this->assertEquals(
            ImageOrientation::PORTRAIT,
            ImageOrientation::fromWidthAndHeight(800, 1200)
        );
        $this->assertEquals(
            ImageOrientation::LANDSCAPE,
            ImageOrientation::fromWidthAndHeight(1200, 800)
        );
        $this->assertEquals(
            ImageOrientation::PORTRAIT,
            ImageOrientation::fromWidthAndHeight(1200, 1200)
        );
    }
}
