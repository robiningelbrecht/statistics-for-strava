<?php

declare(strict_types=1);

namespace App\BuildApp\BuildDashboardHtml\Layout\Widget;

final class WidgetConfiguration
{
    /** @var array<string, mixed> */
    private array $configuration = [];

    public static function empty(): self
    {
        return new self();
    }

    public function add(string $key, int|string|float|bool $value): self
    {
        $this->configuration[$key] = $value;

        return $this;
    }

    public function getConfigItem(string $key, mixed $default = null): int|string|float|bool|null
    {
        return $this->configuration[$key] ?? $default;
    }
}
