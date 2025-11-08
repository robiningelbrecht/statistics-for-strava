<?php

declare(strict_types=1);

namespace App\Infrastructure\Daemon\Cron;

use Cron\CronExpression;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class SystemCron implements Cron
{
    /** @var CronAction[] */
    private array $enabledCronActions;

    /**
     * @param iterable<RunnableCronAction> $runnableCronActions
     */
    public function __construct(
        #[AutowireIterator('app.cron_action')]
        iterable $runnableCronActions,
        private readonly ConfiguredCronActions $configuredCronActions,
    ) {
        $runnableCronActionsKeyedById = [];
        foreach ($runnableCronActions as $runnableCronAction) {
            $runnableCronActionsKeyedById[$runnableCronAction->getId()] = $runnableCronAction;
        }

        $this->enabledCronActions = [];

        foreach ($this->configuredCronActions as $configuredCronAction) {
            if (false === $configuredCronAction['enabled']) {
                continue;
            }
            $id = $configuredCronAction['action'];

            $this->enabledCronActions[$id] = CronAction::create(
                id: $id,
                expression: new CronExpression($configuredCronAction['expression']),
                runnable: $runnableCronActionsKeyedById[$id] ?? throw new \InvalidArgumentException(sprintf('Cron action "%s" does not exists.', $id)),
            );
        }
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->enabledCronActions);
    }

    public function getRunnable(string $id): RunnableCronAction
    {
        $cronAction = $this->enabledCronActions[$id] ?? throw new \InvalidArgumentException(sprintf('Cron runnable "%s" does not exists or is not enabled.', $id));

        return $cronAction->getRunnable();
    }
}
