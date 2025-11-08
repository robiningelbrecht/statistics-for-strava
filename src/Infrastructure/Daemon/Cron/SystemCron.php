<?php

declare(strict_types=1);

namespace App\Infrastructure\Daemon\Cron;

use App\Infrastructure\Console\ConsoleOutputAware;
use Cron\CronExpression;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class SystemCron implements Cron
{
    use ConsoleOutputAware;

    /** @var RunnableCronAction[] */
    private array $runnableCronActions;

    /**
     * @param iterable<RunnableCronAction> $runnableCronActions
     */
    public function __construct(
        #[AutowireIterator('app.cron_action')]
        iterable $runnableCronActions,
        private readonly ConfiguredCronActions $configuredCronActions,
    ) {
        foreach ($runnableCronActions as $runnableCronAction) {
            $this->runnableCronActions[$runnableCronAction->getId()] = $runnableCronAction;
        }
    }

    public function getIterator(): \Traversable
    {
        $cronItems = [];

        foreach ($this->configuredCronActions as $configuredCronAction) {
            $id = $configuredCronAction['action'];
            $runnable = $this->runnableCronActions[$id] ?? throw new \InvalidArgumentException(sprintf('Cron action "%s" does not exists.', $id));

            $cronItems[$id] = CronAction::create(
                id: $id,
                expression: new CronExpression($configuredCronAction['expression']),
                runnable: $runnable,
            );
        }

        return new \ArrayIterator($cronItems);
    }

    public function getRunnable(string $id): RunnableCronAction
    {
        return $this->runnableCronActions[$id] ?? throw new \InvalidArgumentException(sprintf('Cron runnable "%s" does not exists.', $id));
    }
}
