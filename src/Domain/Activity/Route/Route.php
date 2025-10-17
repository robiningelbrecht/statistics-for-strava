<?php

declare(strict_types=1);

namespace App\Domain\Activity\Route;

use App\Domain\Activity\SportType\SportType;
use App\Domain\Activity\WorkoutType;
use App\Domain\Integration\Geocoding\Nominatim\Location;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class Route implements \JsonSerializable
{
    private function __construct(
        private string $encodedPolyline,
        private Location $location,
        private SportType $sportType,
        private bool $isCommute,
        private ?WorkoutType $workoutType,
        private SerializableDateTime $on,
    ) {
    }

    public static function create(
        string $encodedPolyline,
        Location $location,
        SportType $sportType,
        bool $isCommute,
        ?WorkoutType $workoutType,
        SerializableDateTime $on,
    ): self {
        return new self(
            encodedPolyline: $encodedPolyline,
            location: $location,
            sportType: $sportType,
            isCommute: $isCommute,
            workoutType: $workoutType,
            on: $on
        );
    }

    public function getEncodedPolyline(): string
    {
        return $this->encodedPolyline;
    }

    public function getLocation(): Location
    {
        return $this->location;
    }

    public function getSportType(): SportType
    {
        return $this->sportType;
    }

    public function isCommute(): bool
    {
        return $this->isCommute;
    }

    public function getWorkoutType(): ?WorkoutType
    {
        return $this->workoutType;
    }

    public function getOn(): SerializableDateTime
    {
        return $this->on;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $state = $this->getLocation()->getState();

        return [
            'active' => true,
            'location' => [
                'countryCode' => $this->getLocation()->getCountryCode(),
                'state' => $state ? str_replace(['"', '\''], '', $state) : null, // Fix for ISSUE-287
            ],
            'filterables' => [
                'sportType' => $this->getSportType(),
                'start-date' => $this->getOn()->getTimestamp() * 1000, // JS timestamp is in milliseconds,
                'isCommute' => $this->isCommute() ? 'true' : 'false',
                'workoutType' => $this->getWorkoutType()?->value,
            ],
            'encodedPolyline' => $this->getEncodedPolyline(),
        ];
    }
}
