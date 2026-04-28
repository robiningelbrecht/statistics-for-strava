<?php

declare(strict_types=1);

namespace App\Console;

use App\Application\Import\CalculateActivityMetrics\Pipeline\CalculateGap;
use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityIdRepository;
use App\Domain\Activity\ActivityIds;
use App\Domain\Activity\ActivityRepository;
use App\Infrastructure\Console\ProvideConsoleIntro;
use App\Infrastructure\DependencyInjection\Mutex\WithMutex;
use App\Infrastructure\Mutex\LockName;
use App\Infrastructure\Mutex\Mutex;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[WithMutex(lockName: LockName::IMPORT_DATA_OR_BUILD_APP)]
#[AsCommand(name: 'app:strava:recalculate-gap', description: 'Recalculate GAP values for existing running activity splits')]
final class RecalculateGapConsoleCommand extends Command
{
    use ProvideConsoleIntro;

    public function __construct(
        private readonly CalculateGap $calculateGap,
        private readonly ActivityIdRepository $activityIdRepository,
        private readonly ActivityRepository $activityRepository,
        private readonly Mutex $mutex,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('activityId', InputArgument::OPTIONAL, 'Recalculate GAP for a single activity');
        $this->addOption('all', null, InputOption::VALUE_NONE, 'Recalculate GAP for all running activities');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output = new SymfonyStyle($input, $output);
        $this->mutex->acquireLock('RecalculateGapConsoleCommand');

        $this->outputConsoleIntro($output);

        $activityId = $input->getArgument('activityId');
        $recalculateAll = (bool) $input->getOption('all');

        if ((null === $activityId && !$recalculateAll) || (null !== $activityId && $recalculateAll)) {
            $output->error('Provide either an activityId argument or the --all option.');

            return Command::INVALID;
        }

        $activityIdsToProcess = null !== $activityId
            ? ActivityIds::fromArray([ActivityId::fromUnprefixed((string) $activityId)])
            : $this->findRunningActivityIds();

        if ($activityIdsToProcess->isEmpty()) {
            $output->success('No running activities found to recalculate GAP for.');

            return Command::SUCCESS;
        }

        $output->writeln(sprintf('Recalculating GAP for %d activit%s...', count($activityIdsToProcess), 1 === count($activityIdsToProcess) ? 'y' : 'ies'));
        $countActivitiesProcessed = $this->calculateGap->recalculateForActivityIds($output, $activityIdsToProcess);

        $output->success(sprintf('Recalculated GAP for %d activit%s.', $countActivitiesProcessed, 1 === $countActivitiesProcessed ? 'y' : 'ies'));

        return Command::SUCCESS;
    }

    private function findRunningActivityIds(): ActivityIds
    {
        $runningActivityIds = ActivityIds::empty();

        foreach ($this->activityIdRepository->findAll() as $activityId) {
            if (!$this->activityRepository->find($activityId)->getSportType()->getActivityType()->supportsGapStats()) {
                continue;
            }

            $runningActivityIds->add($activityId);
        }

        return $runningActivityIds;
    }
}
