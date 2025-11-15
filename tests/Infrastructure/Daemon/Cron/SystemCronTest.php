<?php

namespace App\Tests\Infrastructure\Daemon\Cron;

use App\Infrastructure\Daemon\Cron\ConfiguredCronActions;
use App\Infrastructure\Daemon\Cron\CronAction;
use App\Infrastructure\Daemon\Cron\SystemCron;
use App\Infrastructure\Serialization\Json;
use App\Tests\ContainerTestCase;
use Cron\CronExpression;
use Spatie\Snapshots\MatchesSnapshots;

class SystemCronTest extends ContainerTestCase
{
    use MatchesSnapshots;

    public function testItRegistersCronActions(): void
    {
        /** @var SystemCron $cron */
        $cron = $this->getContainer()->get(SystemCron::class);

        $snapshot = [];
        /** @var \App\Infrastructure\Daemon\Cron\RunnableCronAction $action */
        foreach ($cron->getAllRunnableCronActions() as $action) {
            $snapshot[$action->getId()] = [
                'requiresDatabaseSchemaToBeUpdated' => $action->requiresDatabaseSchemaToBeUpdated(),
                'mutexTtl' => $action->getMutexTtl(),
            ];
        }

        $this->assertMatchesJsonSnapshot(Json::encode($snapshot));
    }

    public function testGetIterator(): void
    {
        $configuredCronActions = ConfiguredCronActions::fromConfig([
            [
                'action' => 'fake',
                'expression' => '* * * * *',
                'enabled' => true,
            ],
        ]);
        $runnableCronActions = [
            new FakeRunnableCronAction(),
        ];

        $cron = new SystemCron(
            runnableCronActions: $runnableCronActions,
            configuredCronActions: $configuredCronActions,
        );

        $this->assertEquals(
            [
                'fake' => CronAction::create(
                    id: 'fake',
                    expression: new CronExpression('* * * * *'),
                    runnable: new FakeRunnableCronAction(),
                ),
            ],
            iterator_to_array($cron)
        );
    }

    public function testItShouldThrowOnInvalidAction(): void
    {
        $configuredCronActions = ConfiguredCronActions::fromConfig([
            [
                'action' => 'test',
                'expression' => '* * * * *',
                'enabled' => true,
            ],
        ]);
        $runnableCronActions = [
            new FakeRunnableCronAction(),
        ];

        $this->expectExceptionObject(new \InvalidArgumentException('Cron action "test" does not exists.'));
        $cron = new SystemCron(
            runnableCronActions: $runnableCronActions,
            configuredCronActions: $configuredCronActions,
        );
    }

    public function testGetRunnable(): void
    {
        $configuredCronActions = ConfiguredCronActions::fromConfig([
            [
                'action' => 'fake',
                'expression' => '* * * * *',
                'enabled' => true,
            ],
        ]);
        $runnableCronActions = [
            new FakeRunnableCronAction(),
        ];

        $cron = new SystemCron(
            runnableCronActions: $runnableCronActions,
            configuredCronActions: $configuredCronActions,
        );

        $this->assertEquals(
            new FakeRunnableCronAction(),
            $cron->getRunnable('fake'),
        );
    }

    public function testGetRunnableItShouldThrow(): void
    {
        $configuredCronActions = ConfiguredCronActions::fromConfig([
            [
                'action' => 'fake',
                'expression' => '* * * * *',
                'enabled' => false,
            ],
        ]);
        $runnableCronActions = [
            new FakeRunnableCronAction(),
        ];

        $cron = new SystemCron(
            runnableCronActions: $runnableCronActions,
            configuredCronActions: $configuredCronActions,
        );

        $this->expectExceptionObject(new \InvalidArgumentException('Cron runnable "fake" does not exists or is not enabled.'));
        $this->assertEquals(
            new FakeRunnableCronAction(),
            $cron->getRunnable('fake'),
        );
    }
}
