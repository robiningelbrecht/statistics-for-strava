<?php

declare(strict_types=1);

namespace App\Tests;

use App\Application\AppVersion;
use PHPUnit\Framework\TestCase;

class ChangelogTest extends TestCase
{
    private const string CVE_REPORT_REQUIRED_SINCE = '4.6.5';

    public function testThatChangelogContainsLatestVersion(): void
    {
        $changelog = file_get_contents(__DIR__.'/../CHANGELOG.md');

        $latestVersion = AppVersion::getSemanticVersion();
        $this->assertStringContainsString(
            sprintf('# [%s](https://github.com/robiningelbrecht/statistics-for-strava/releases/tag/%s', $latestVersion, $latestVersion),
            $changelog
        );
    }

    public function testThatEachReleaseContainsCveReport(): void
    {
        $changelog = file_get_contents(__DIR__.'/../CHANGELOG.md');
        $releases = preg_split('/^(?=# \[v\d+\.\d+\.\d+\])/m', $changelog, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($releases as $release) {
            preg_match('/^# \[(v\d+\.\d+\.\d+)\]/', $release, $matches);
            $version = $matches[1] ?? null;

            if (null === $version || version_compare(ltrim($version, 'v'), self::CVE_REPORT_REQUIRED_SINCE, '<')) {
                continue;
            }

            $this->assertStringContainsString(
                '<h4>Docker Image CVE Report</h4>',
                $release,
                sprintf('Release %s is missing a "Docker Image CVE Report" section', $version),
            );
        }
    }
}
