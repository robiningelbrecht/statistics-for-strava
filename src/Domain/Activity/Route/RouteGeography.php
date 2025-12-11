<?php

declare(strict_types=1);

namespace App\Domain\Activity\Route;

final readonly class RouteGeography implements \JsonSerializable
{
    public const string IS_REVERSE_GEOCODED = 'is_reverse_geocoded';
    public const string PASSED_TROUGH_COUNTRIES = 'passed_through_countries';

    /**
     * @param array<string, mixed> $data
     */
    private function __construct(
        private array $data,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function create(array $data): self
    {
        return new self($data);
    }

    public function isEmpty(): bool
    {
        return empty($this->data);
    }

    public function getStartingPointCountryCode(): ?string
    {
        return $this->data['country_code'] ?? null;
    }

    public function getStartingPointState(): ?string
    {
        return $this->data['state'] ?? $this->data['county'] ?? null;
    }

    public function isReversedGeocoded(): bool
    {
        return $this->data[self::IS_REVERSE_GEOCODED] ?? false;
    }

    public function hasBeenAnalyzedForRouteGeography(): bool
    {
        return array_key_exists(self::PASSED_TROUGH_COUNTRIES, $this->data);
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->data;
    }
}
