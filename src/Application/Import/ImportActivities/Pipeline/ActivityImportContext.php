<?php

namespace App\Application\Import\ImportActivities\Pipeline;

use App\Domain\Activity\Activity;
use App\Domain\Gear\Gears;

final readonly class ActivityImportContext
{
    private function __construct(
        /** @var array<string, mixed> */
        private array $rawStravaData,
        private Gears $allGear,
        private ?bool $isNewActivity,
        private ?Activity $activity,
    ) {
    }

    /**
     * @param array<string, mixed> $rawStravaData
     */
    public static function create(array $rawStravaData, Gears $allGear): self
    {
        return new self(
            rawStravaData: $rawStravaData,
            allGear: $allGear,
            isNewActivity: null,
            activity: null,
        );
    }

    public function withIsNewActivity(bool $isNewActivity): self
    {
        return clone ($this, [
            'isNewActivity' => $isNewActivity,
        ]);
    }

    public function withActivity(Activity $activity): self
    {
        return clone ($this, [
            'activity' => $activity,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getRawStravaData(): array
    {
        return $this->rawStravaData;
    }

    public function getAllGear(): Gears
    {
        return $this->allGear;
    }

    public function isNewActivity(): ?bool
    {
        return $this->isNewActivity;
    }

    public function getActivity(): ?Activity
    {
        return $this->activity;
    }
}
