<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Widget;

final class WidgetConfiguration
{
    /** @var array<string, mixed> */
    private array $configuration = [];

    public static function empty(): self
    {
        return new self();
    }

    /**
     * @param int|string|float|bool|array<int, int|string> $value
     */
    public function add(string $key, int|string|float|bool|array $value): self
    {
        $this->configuration[$key] = $value;

        return $this;
    }

    /**
     * @return int|string|float|bool|array<int, int|string>|null $value
     */
    public function getConfigItem(string $key, mixed $default = null): int|string|float|bool|array|null
    {
        return $this->configuration[$key] ?? $default;
    }

    public function configItemExists(string $key): bool
    {
        return array_key_exists($key, $this->configuration);
    }
}
