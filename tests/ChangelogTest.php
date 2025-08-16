<?php

declare(strict_types=1);

namespace App\Tests;

use App\BuildApp\AppVersion;
use PHPUnit\Framework\TestCase;

class ChangelogTest extends TestCase
{
    public function testThatChangelogContainsLatestVersion(): void
    {
        $changelog = file_get_contents(__DIR__.'/../CHANGELOG.md');

        $latestVersion = AppVersion::getSemanticVersion();
        $this->assertStringContainsString(sprintf('# [%s]', $latestVersion), $changelog);
    }
}
