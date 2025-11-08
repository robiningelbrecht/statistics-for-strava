<?php

namespace App\Tests\Infrastructure\Cron;

use App\Infrastructure\Cron\ConfiguredCronActions;
use App\Infrastructure\Cron\CronAction;
use App\Infrastructure\Cron\SystemCron;
use Cron\CronExpression;
use PHPUnit\Framework\TestCase;

class SystemCronTest extends TestCase
{
    public function testGetIterator(): void
    {
        $configuredCronActions = ConfiguredCronActions::fromConfig([
            [
                'action' => 'fake',
                'expression' => '* * * * *',
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
            ],
        ]);
        $runnableCronActions = [
            new FakeRunnableCronAction(),
        ];

        $cron = new SystemCron(
            runnableCronActions: $runnableCronActions,
            configuredCronActions: $configuredCronActions,
        );

        $this->expectExceptionObject(new \InvalidArgumentException('Cron action "test" does not exists.'));
        iterator_to_array($cron);
    }

    public function testGetRunnable(): void
    {
        $configuredCronActions = ConfiguredCronActions::fromConfig([
            [
                'action' => 'fake',
                'expression' => '* * * * *',
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
            ],
        ]);
        $runnableCronActions = [
            new FakeRunnableCronAction(),
        ];

        $cron = new SystemCron(
            runnableCronActions: $runnableCronActions,
            configuredCronActions: $configuredCronActions,
        );

        $this->expectExceptionObject(new \InvalidArgumentException('Cron runnable "test" does not exists.'));
        $this->assertEquals(
            new FakeRunnableCronAction(),
            $cron->getRunnable('test'),
        );
    }
}
