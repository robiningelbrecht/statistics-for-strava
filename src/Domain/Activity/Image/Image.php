<?php

namespace App\Domain\Activity\Image;

use App\Domain\Activity\Activity;

final readonly class Image
{
    private function __construct(
        private string $imageLocation,
        private Activity $activity,
    ) {
    }

    public static function create(
        string $imageLocation,
        Activity $activity,
    ): self {
        return new self(
            imageLocation: $imageLocation,
            activity: $activity
        );
    }

    public function getImageUrl(): string
    {
        if (str_starts_with($this->imageLocation, '/')) {
            return $this->imageLocation;
        }

        return '/'.$this->imageLocation;
    }

    public function getActivity(): Activity
    {
        return $this->activity;
    }

    /**
     * @return array<string, mixed>
     */
    public function getFilterables(): array
    {
        $activity = $this->getActivity();

        return [
            'sportType' => $activity->getSportType()->value,
            'countryCode' => $activity->getLocation()?->getCountryCode(),
        ];
    }
}
