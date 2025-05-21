<?php

namespace App\Tests\Infrastructure\Twig;

use App\Domain\App\AppUrl;
use App\Infrastructure\Twig\UrlTwigExtension;
use PHPUnit\Framework\TestCase;

class UrlTwigExtensionTest extends TestCase
{
    public function testToAbsoluteUrl(): void
    {
        $this->assertEquals(
            '/test/path',
            new UrlTwigExtension(AppUrl::fromString('http://localhost:8081'))->toRelativeUrl('test/path')
        );
        $this->assertEquals(
            '/test/path',
            new UrlTwigExtension(AppUrl::fromString('http://localhost:8081'))->toRelativeUrl('/test/path')
        );
        $this->assertEquals(
            '/base/test/path',
            new UrlTwigExtension(AppUrl::fromString('http://localhost:8081/base/'))->toRelativeUrl('test/path')
        );
        $this->assertEquals(
            '/base/test/path',
            new UrlTwigExtension(AppUrl::fromString('http://localhost:8081/base/'))->toRelativeUrl('/test/path')
        );
    }
}
