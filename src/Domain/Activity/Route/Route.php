<?php

declare(strict_types=1);

namespace App\Domain\Activity\Route;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Activity\WorkoutType;
use App\Infrastructure\Serialization\Escape;
use App\Infrastructure\Time\Format\DateAndTimeFormat;
use App\Infrastructure\ValueObject\Measurement\Length\Kilometer;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final class Route implements \JsonSerializable
{
    private UnitSystem $unitSystem;
    private DateAndTimeFormat $dateAndTimeFormat;
    private string $relativeActivityUri;

    private function __construct(
        private readonly ActivityId $activityId,
        private readonly string $name,
        private readonly Kilometer $distance,
        private readonly string $encodedPolyline,
        private readonly RouteGeography $routeGeography,
        private readonly SportType $sportType,
        private readonly bool $isCommute,
        private readonly ?WorkoutType $workoutType,
        private readonly SerializableDateTime $on,
    ) {
    }

    public static function create(
        ActivityId $activityId,
        string $name,
        Kilometer $distance,
        string $encodedPolyline,
        RouteGeography $routeGeography,
        SportType $sportType,
        bool $isCommute,
        ?WorkoutType $workoutType,
        SerializableDateTime $on,
    ): self {
        return new self(
            activityId: $activityId,
            name: $name,
            distance: $distance,
            encodedPolyline: $encodedPolyline,
            routeGeography: $routeGeography,
            sportType: $sportType,
            isCommute: $isCommute,
            workoutType: $workoutType,
            on: $on,
        );
    }

    public function getActivityId(): ActivityId
    {
        return $this->activityId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDistance(): Kilometer
    {
        return $this->distance;
    }

    public function getEncodedPolyline(): string
    {
        return $this->encodedPolyline;
    }

    public function getRouteGeography(): RouteGeography
    {
        return $this->routeGeography;
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

    public function enrichWithUnitSystemAndDateTimeFormat(
        UnitSystem $unitSystem,
        DateAndTimeFormat $dateAndTimeFormat,
    ): void {
        $this->unitSystem = $unitSystem;
        $this->dateAndTimeFormat = $dateAndTimeFormat;
    }

    public function enrichWithRelativeActivityUri(string $uri): void
    {
        $this->relativeActivityUri = $uri;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $state = $this->getRouteGeography()->getStartingPointState();

        $distance = $this->getDistance();
        if (isset($this->unitSystem)) {
            $distance = $distance->toUnitSystem($this->unitSystem);
        }
        $distanceInScalar = $distance->toFloat();
        $precision = $distanceInScalar < 100 ? 1 : 0;
        $distance = number_format(round($distanceInScalar, $precision), $precision, '.', ' ').$distance->getSymbol();

        $startDate = isset($this->dateAndTimeFormat) ?
            $this->getOn()->format((string) $this->dateAndTimeFormat->getDateFormatNormal()) :
            $this->getOn()->format('d-m-Y');

        return [
            'active' => true,
            'id' => $this->getActivityId(),
            'activityUrl' => $this->relativeActivityUri ?? null,
            'startDate' => $startDate,
            'distance' => $distance,
            'name' => Escape::forJsonEncode($this->getName()),
            'startLocation' => [
                'countryCode' => $this->getRouteGeography()->getStartingPointCountryCode(),
                'state' => $state ? Escape::forJsonEncode($state) : null,
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
