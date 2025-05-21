<?php

namespace App\Tests\Infrastructure\Twig;

use App\Infrastructure\Twig\UrlTwigExtension;
use App\Infrastructure\ValueObject\String\BasePath;
use PHPUnit\Framework\TestCase;

class UrlTwigExtensionTest extends TestCase
{
    public function testToAbsoluteUrl(): void
    {
        $this->assertEquals(
            '/test/path',
            new UrlTwigExtension(null)->toRelativeUrl('test/path')
        );
        $this->assertEquals(
            '/test/path',
            new UrlTwigExtension(null)->toRelativeUrl('/test/path')
        );
        $this->assertEquals(
            '/base/test/path',
            new UrlTwigExtension(BasePath::fromString('base'))->toRelativeUrl('test/path')
        );
        $this->assertEquals(
            '/base/test/path',
            new UrlTwigExtension(BasePath::fromString('/base/'))->toRelativeUrl('/test/path')
        );
    }
}
