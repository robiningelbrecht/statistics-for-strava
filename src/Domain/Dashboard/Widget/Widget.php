<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Widget;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.dashboard.widget')]
interface Widget
{
    public function getDefaultConfiguration(): WidgetConfiguration;

    /**
     * @param array<string, mixed> $config
     */
    public function guardValidConfiguration(array $config): void;

    public function render(SerializableDateTime $now, WidgetConfiguration $configuration): ?string;
}
