<?php

namespace App\Console;

use App\Application\Import\DeleteActivitiesMarkedForDeletion\DeleteActivitiesMarkedForDeletion;
use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityIds;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawDataRepository;
use App\Domain\Activity\Stream\ActivityStreamRepository;
use App\Domain\Activity\Stream\CombinedStream\CombinedActivityStreamRepository;
use App\Infrastructure\Console\ProvideConsoleIntro;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\Daemon\Mutex\LockName;
use App\Infrastructure\Daemon\Mutex\Mutex;
use App\Infrastructure\DependencyInjection\Mutex\WithMutex;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[WithMutex(lockName: LockName::IMPORT_DATA_OR_BUILD_APP)]
#[AsCommand(name: 'app:data:detect-corrupted-activities', description: 'Checks for corrupted activities and deletes them')]
class DetectCorruptedActivitiesConsoleCommand extends Command
{
    use ProvideConsoleIntro;

    public function __construct(
        private readonly ActivityRepository $activityRepository,
        private readonly ActivityWithRawDataRepository $activityWithRawDataRepository,
        private readonly ActivityStreamRepository $activityStreamRepository,
        private readonly CombinedActivityStreamRepository $combinedActivityStreamRepository,
        private readonly CommandBus $commandBus,
        private readonly UnitSystem $unitSystem,
        private readonly Mutex $mutex,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output = new SymfonyStyle($input, $output);
        $this->mutex->acquireLock('BuildAppConsoleCommand');

        $this->outputConsoleIntro($output);

        $progressIndicator = new ProgressIndicator(
            output: $output,
            format: null,
            indicatorChangeInterval: 100,
            indicatorValues: ['⠏', '⠛', '⠹', '⢸', '⣰', '⣤', '⣆', '⡇']
        );
        $progressIndicator->start('Scanning activities...');

        $activityIds = $this->activityRepository->findActivityIds();
        $activityIdsToDelete = ActivityIds::empty();
        foreach ($activityIds as $activityId) {
            try {
                $this->activityWithRawDataRepository->find($activityId);
            } catch (\JsonException) {
                $activityIdsToDelete->add($activityId);
                continue;
            }

            try {
                $this->activityStreamRepository->findByActivityId($activityId);
            } catch (\JsonException) {
                $activityIdsToDelete->add($activityId);
                continue;
            }

            try {
                $this->combinedActivityStreamRepository->findOneForActivityAndUnitSystem(
                    activityId: $activityId,
                    unitSystem: $this->unitSystem,
                );
            } catch (EntityNotFound) {
            } catch (\JsonException) {
                $activityIdsToDelete->add($activityId);
                continue;
            }
            $progressIndicator->advance();
        }

        if ($activityIdsToDelete->isEmpty()) {
            $progressIndicator->finish('No activities with corrupted data found');

            return Command::SUCCESS;
        }

        $progressIndicator->finish(sprintf('Found %d activities with corrupted data', count($activityIdsToDelete)));
        $output->newLine();
        $output->listing(array_map(function (ActivityId $activityId) {
            $activity = $this->activityRepository->findSummary($activityId);

            return sprintf(
                'Activity "%s - %s"',
                $activity->getName(),
                $activity->getStartDate()->format('d-m-Y')
            );
        }, $activityIdsToDelete->toArray()));

        if (!$output->confirm('Do you want to delete these activities so they can be re-imported in the next run?')) {
            return Command::SUCCESS;
        }

        $this->activityWithRawDataRepository->markActivitiesForDeletion($activityIdsToDelete);
        $this->commandBus->dispatch(new DeleteActivitiesMarkedForDeletion($output));

        return Command::SUCCESS;
    }
}
