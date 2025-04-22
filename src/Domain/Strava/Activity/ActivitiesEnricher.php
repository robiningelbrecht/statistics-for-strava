<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity;

use App\Domain\Strava\Activity\Stream\ActivityPowerRepository;
use App\Domain\Strava\Activity\Stream\ActivityStreamRepository;
use App\Domain\Strava\Activity\Stream\StreamType;
use App\Domain\Strava\Gear\Maintenance\GearMaintenanceConfig;
use App\Infrastructure\Exception\EntityNotFound;

final class ActivitiesEnricher
{
    private Activities $enrichedActivities;
    /** @var array<string, Activities> */
    private array $activitiesPerActivityType;

    public function __construct(
        private readonly ActivityRepository $activityRepository,
        private readonly ActivityPowerRepository $activityPowerRepository,
        private readonly ActivityStreamRepository $activityStreamRepository,
        private readonly ActivityTypeRepository $activityTypeRepository,
        private readonly GearMaintenanceConfig $gearMaintenanceConfig,
    ) {
        $this->enrichedActivities = Activities::empty();
        $this->activitiesPerActivityType = [];
    }

    private function enrichAll(): Activities
    {
        $maintenanceTags = $this->gearMaintenanceConfig->getAllMaintenanceTags();
        $activities = $this->activityRepository->findAll();

        foreach ($activities as $activity) {
            $activity->enrichWithBestPowerOutputs(
                $this->activityPowerRepository->findBestForActivity($activity->getId())
            );
            $activity->enrichWithMaintenanceTags($maintenanceTags);

            try {
                $cadenceStream = $this->activityStreamRepository->findOneByActivityAndStreamType(
                    activityId: $activity->getId(),
                    streamType: StreamType::CADENCE
                );

                if (!empty($cadenceStream->getData())) {
                    $activity->enrichWithMaxCadence(max($cadenceStream->getData()));
                }
            } catch (EntityNotFound) {
            }
        }

        $this->enrichedActivities = $activities;

        return $activities;
    }

    public function getEnrichedActivities(): Activities
    {
        if ($this->enrichedActivities->isEmpty()) {
            $this->enrichedActivities = $this->enrichAll();
        }

        return $this->enrichedActivities;
    }

    /**
     * @return array<string, Activities>
     */
    public function getActivitiesPerActivityType(): array
    {
        if (empty($this->activitiesPerActivityType)) {
            $activityTypes = $this->activityTypeRepository->findAll();

            foreach ($activityTypes as $activityType) {
                $this->activitiesPerActivityType[$activityType->value] = $this->getEnrichedActivities()->filterOnActivityType($activityType);
            }
        }

        return $this->activitiesPerActivityType;
    }
}
