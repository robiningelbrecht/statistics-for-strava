<?php

declare(strict_types=1);

namespace App\Application\Import\ProcessRawActivityData\Pipeline;

use App\Domain\Activity\ActivityIdRepository;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\Strength\StrengthWorkoutDescriptionParser;
use App\Domain\Activity\Strength\StrengthWorkoutRepository;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class ProcessStrengthWorkouts implements ProcessRawDataStep
{
    public function __construct(
        private ActivityIdRepository $activityIdRepository,
        private ActivityRepository $activityRepository,
        private StrengthWorkoutRepository $strengthWorkoutRepository,
        private StrengthWorkoutDescriptionParser $strengthWorkoutDescriptionParser,
    ) {
    }

    public function process(OutputInterface $output): void
    {
        $count = 0;
        foreach ($this->activityIdRepository->findAll() as $activityId) {
            if ($this->strengthWorkoutRepository->isImportedForActivity($activityId)) {
                continue;
            }

            $activity = $this->activityRepository->find($activityId);
            $exercises = $this->strengthWorkoutDescriptionParser->parse($activity->getDescription());

            if ($exercises->isEmpty()) {
                continue;
            }

            $this->strengthWorkoutRepository->saveForActivity($activityId, $exercises);
            ++$count;
        }

        $output->writeln(sprintf('  => Parsed strength data for %d activities', $count));
    }
}
