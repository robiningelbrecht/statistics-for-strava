<?php

namespace App\Tests\Infrastructure\Twig;

use App\BuildApp\AppUrl;
use App\Domain\Activity\Image\ImageOrientation;
use App\Infrastructure\Twig\StringTwigExtension;
use App\Infrastructure\Twig\SvgsTwigExtension;
use App\Infrastructure\Twig\UrlTwigExtension;
use App\Tests\ContainerTestCase;

class UrlTwigExtensionTest extends ContainerTestCase
{
    private StringTwigExtension $stringTwigExtension;
    private SvgsTwigExtension $svgsTwigExtension;

    public function testToAbsoluteUrl(): void
    {
        $this->assertEquals(
            '/test/path',
            new UrlTwigExtension(
                appUrl: AppUrl::fromString('http://localhost:8081'),
                stringTwigExtension: $this->stringTwigExtension,
                svgsTwigExtension: $this->svgsTwigExtension,
            )->toRelativeUrl('test/path')
        );
        $this->assertEquals(
            '/test/path',
            new UrlTwigExtension(
                appUrl: AppUrl::fromString('http://localhost:8081'),
                stringTwigExtension: $this->stringTwigExtension,
                svgsTwigExtension: $this->svgsTwigExtension,
            )->toRelativeUrl('/test/path')
        );
        $this->assertEquals(
            '/base/test/path',
            new UrlTwigExtension(
                appUrl: AppUrl::fromString('http://localhost:8081/base/'),
                stringTwigExtension: $this->stringTwigExtension,
                svgsTwigExtension: $this->svgsTwigExtension,
            )->toRelativeUrl('test/path')
        );
        $this->assertEquals(
            '/base/test/path',
            new UrlTwigExtension(
                appUrl: AppUrl::fromString('http://localhost:8081/base/'),
                stringTwigExtension: $this->stringTwigExtension,
                svgsTwigExtension: $this->svgsTwigExtension,
            )->toRelativeUrl('/test/path')
        );
    }

    public function testPlaceholderImage(): void
    {
        $this->assertEquals(
            '/assets/placeholder.webp',
            new UrlTwigExtension(
                appUrl: AppUrl::fromString('http://localhost:8081'),
                stringTwigExtension: $this->stringTwigExtension,
                svgsTwigExtension: $this->svgsTwigExtension,
            )->placeholderImage()
        );

        $this->assertEquals(
            '/assets/placeholder-portrait.webp',
            new UrlTwigExtension(
                appUrl: AppUrl::fromString('http://localhost:8081'),
                stringTwigExtension: $this->stringTwigExtension,
                svgsTwigExtension: $this->svgsTwigExtension,
            )->placeholderImage(ImageOrientation::PORTRAIT)
        );
    }

    protected function setUp(): void
    {
        $this->stringTwigExtension = new StringTwigExtension();
        $this->svgsTwigExtension = new SvgsTwigExtension();
    }
}
