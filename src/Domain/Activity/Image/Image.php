<?php

namespace App\Domain\Activity\Image;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\SportType\SportType;

final readonly class Image
{
    /**
     * @param string[] $relatedCountryCodes
     */
    private function __construct(
        private string $imageLocation,
        private ActivityId $activityId,
        private SportType $sportType,
        private ImageOrientation $orientation,
        private array $relatedCountryCodes,
    ) {
    }

    /**
     * @param string[] $relatedCountryCodes
     */
    public static function create(
        string $imageLocation,
        ActivityId $activityId,
        SportType $sportType,
        ImageOrientation $orientation,
        array $relatedCountryCodes,
    ): self {
        return new self(
            imageLocation: $imageLocation,
            activityId: $activityId,
            sportType: $sportType,
            orientation: $orientation,
            relatedCountryCodes: $relatedCountryCodes,
        );
    }

    public function getImageUrl(): string
    {
        if (str_starts_with($this->imageLocation, '/')) {
            return $this->imageLocation;
        }

        return '/'.$this->imageLocation;
    }

    public function getActivityId(): ActivityId
    {
        return $this->activityId;
    }

    public function getSportType(): SportType
    {
        return $this->sportType;
    }

    /**
     * @return string[]
     */
    public function getRelatedCountryCodes(): array
    {
        return $this->relatedCountryCodes;
    }

    public function getOrientation(): ImageOrientation
    {
        return $this->orientation;
    }

    /**
     * @return array<string, mixed>
     */
    public function getFilterables(): array
    {
        return [
            'sportType' => $this->getSportType()->value,
            'countryCode' => $this->getRelatedCountryCodes(),
        ];
    }
}
