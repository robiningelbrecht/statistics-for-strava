<?php

namespace App\Tests\Infrastructure\Twig;

use App\Infrastructure\Twig\HtmlTwigExtension;
use PHPUnit\Framework\TestCase;

class HtmlTwigExtensionTest extends TestCase
{
    public function testCleanUniqueId(): void
    {
        $twigExtension = new HtmlTwigExtension();
        $twigExtension::$seenIds = [];
        $id = 'test';

        $this->assertEquals(
            '--1',
            $twigExtension->uniqueNumberForId($id)
        );
        $this->assertEquals(
            '--2',
            $twigExtension->uniqueNumberForId($id)
        );
    }
}
