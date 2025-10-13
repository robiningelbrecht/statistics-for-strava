<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Widget;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class ZwiftStatsWidget implements Widget
{
    public function getDefaultConfiguration(): WidgetConfiguration
    {
        return WidgetConfiguration::empty();
    }

    public function guardValidConfiguration(WidgetConfiguration $configuration): void
    {
    }

    public function render(SerializableDateTime $now, WidgetConfiguration $configuration): ?string
    {
        return null;
    }
}
