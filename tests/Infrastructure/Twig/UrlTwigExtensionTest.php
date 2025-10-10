<?php

namespace App\Tests\Infrastructure\Twig;

use App\BuildApp\AppUrl;
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

    protected function setUp(): void
    {
        $this->stringTwigExtension = new StringTwigExtension();
        $this->svgsTwigExtension = new SvgsTwigExtension();
    }
}
