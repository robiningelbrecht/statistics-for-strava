<?php

namespace App\Tests\Infrastructure\Twig;

use App\Infrastructure\Twig\UrlTwigExtension;
use PHPUnit\Framework\TestCase;

class UrlTwigExtensionTest extends TestCase
{
    public function testToAbsoluteUrl(): void
    {
        $this->assertEquals(
            '/test/path',
            new UrlTwigExtension()->toAbsoluteUrl('test/path')
        );
        $this->assertEquals(
            '/test/path',
            new UrlTwigExtension()->toAbsoluteUrl('/test/path')
        );
    }
}
