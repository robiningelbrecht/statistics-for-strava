<?php

declare(strict_types=1);

namespace App\Application\RunBuild;

use App\Application\Build\BuildActivitiesHtml\BuildActivitiesHtml;
use App\Application\Build\BuildBadgeSvg\BuildBadgeSvg;
use App\Application\Build\BuildBestEffortsHtml\BuildBestEffortsHtml;
use App\Application\Build\BuildChallengesHtml\BuildChallengesHtml;
use App\Application\Build\BuildDashboardHtml\BuildDashboardHtml;
use App\Application\Build\BuildEddingtonHtml\BuildEddingtonHtml;
use App\Application\Build\BuildGearMaintenanceHtml\BuildGearMaintenanceHtml;
use App\Application\Build\BuildGearStatsHtml\BuildGearStatsHtml;
use App\Application\Build\BuildGpxFiles\BuildGpxFiles;
use App\Application\Build\BuildHeatmapHtml\BuildHeatmapHtml;
use App\Application\Build\BuildIndexHtml\BuildIndexHtml;
use App\Application\Build\BuildManifest\BuildManifest;
use App\Application\Build\BuildMonthlyStatsHtml\BuildMonthlyStatsHtml;
use App\Application\Build\BuildPhotosHtml\BuildPhotosHtml;
use App\Application\Build\BuildRewindHtml\BuildRewindHtml;
use App\Application\Build\BuildSegmentsHtml\BuildSegmentsHtml;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

enum BuildStep: string
{
    case INDEX = 'index';
    case ACTIVITIES = 'activities';
    case SEGMENTS = 'segments';
    case DASHBOARD = 'dashboard';
    case HEATMAP = 'heatmap';
    case MONTHLY_STATS = 'monthly-stats';
    case GPX_FILES = 'gpx-files';
    case GEAR_STATS = 'gear-stats';
    case REWIND = 'rewind';
    case CHALLENGES = 'challenges';
    case BEST_EFFORTS = 'best-efforts';
    case EDDINGTON = 'eddington';
    case GEAR_MAINTENANCE = 'gear-maintenance';
    case MANIFEST = 'manifest';
    case PHOTOS = 'photos';
    case BADGES = 'badges';

    public function getLabel(): string
    {
        return match ($this) {
            self::MANIFEST => 'Built manifest',
            self::INDEX => 'Built index',
            self::DASHBOARD => 'Built dashboard',
            self::ACTIVITIES => 'Built activities',
            self::GPX_FILES => 'Built gpx files',
            self::MONTHLY_STATS => 'Built monthly stats',
            self::GEAR_STATS => 'Built gear stats',
            self::GEAR_MAINTENANCE => 'Built gear maintenance',
            self::EDDINGTON => 'Built eddington',
            self::SEGMENTS => 'Built segments',
            self::HEATMAP => 'Built heatmap',
            self::BEST_EFFORTS => 'Built best efforts',
            self::REWIND => 'Built rewind',
            self::CHALLENGES => 'Built challenges',
            self::PHOTOS => 'Built photos',
            self::BADGES => 'Built badges',
        };
    }

    /**
     * Spreads the heaviest steps (ACTIVITIES, SEGMENTS, DASHBOARD) across groups
     * to balance total processing time per process.
     */
    public function getProcessGroup(): int
    {
        return match ($this) {
            self::ACTIVITIES,
            self::HEATMAP,
            self::MONTHLY_STATS,
            self::GPX_FILES,
            self::GEAR_STATS,
            self::REWIND,
            self::EDDINGTON,
            self::GEAR_MAINTENANCE => 0,
            self::INDEX,
            self::SEGMENTS,
            self::DASHBOARD,
            self::CHALLENGES,
            self::BEST_EFFORTS,
            self::MANIFEST,
            self::PHOTOS,
            self::BADGES => 1,
        };
    }

    /**
     * @return array<int, BuildStep[]>
     */
    public static function getProcessGroups(): array
    {
        $groups = [];
        foreach (self::cases() as $step) {
            $groups[$step->getProcessGroup()][] = $step;
        }

        ksort($groups);

        return array_values($groups);
    }

    public function createCommand(SerializableDateTime $now): Command
    {
        return match ($this) {
            self::MANIFEST => new BuildManifest(),
            self::INDEX => new BuildIndexHtml($now),
            self::DASHBOARD => new BuildDashboardHtml(),
            self::ACTIVITIES => new BuildActivitiesHtml($now),
            self::SEGMENTS => new BuildSegmentsHtml(),
            self::GPX_FILES => new BuildGpxFiles(),
            self::MONTHLY_STATS => new BuildMonthlyStatsHtml($now),
            self::GEAR_STATS => new BuildGearStatsHtml($now),
            self::GEAR_MAINTENANCE => new BuildGearMaintenanceHtml(),
            self::EDDINGTON => new BuildEddingtonHtml($now),
            self::HEATMAP => new BuildHeatmapHtml($now),
            self::BEST_EFFORTS => new BuildBestEffortsHtml(),
            self::REWIND => new BuildRewindHtml($now),
            self::CHALLENGES => new BuildChallengesHtml($now),
            self::PHOTOS => new BuildPhotosHtml(),
            self::BADGES => new BuildBadgeSvg($now),
        };
    }
}
