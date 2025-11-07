<?php

namespace App\Tests\Infrastructure\Cron;

use App\Infrastructure\Cron\ConfiguredCronActions;
use App\Infrastructure\Cron\InvalidCronConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ConfiguredCronActionsTest extends TestCase
{
    public function testFromConfig(): void
    {
        $this->assertEquals(
            [
                [
                    'action' => 'sendNotification',
                    'expression' => '* * * * *',
                ],
                [
                    'action' => 'importData',
                    'expression' => '* * * * *',
                ],
            ],
            iterator_to_array(ConfiguredCronActions::fromConfig([
                [
                    'action' => 'sendNotification',
                    'expression' => '* * * * *',
                ],
                [
                    'action' => 'importData',
                    'expression' => '* * * * *',
                ],
            ]))
        );
    }

    #[DataProvider(methodName: 'provideInvalidConfig')]
    public function testFromConfigItShouldThrow(array $config, string $expectedException): void
    {
        $this->expectExceptionObject(new InvalidCronConfig($expectedException));
        ConfiguredCronActions::fromConfig($config);
    }

    public static function provideInvalidConfig(): iterable
    {
        yield 'config is not an array' => [['lol'], 'each configured cron item needs to be an array'];

        $config = self::getValidConfig();
        unset($config[0]['action']);
        yield 'missing key "action"' => [$config, '"action" property is required'];

        $config = self::getValidConfig();
        unset($config[0]['expression']);
        yield 'missing key "expression"' => [$config, '"expression" property is required'];

        $config = self::getValidConfig();
        $config[0]['expression'] = 'lol';
        yield 'invalid cron expression' => [$config, '"lol" is not a valid cron expression'];

        $config = self::getValidConfig();
        $config[1]['action'] = 'sendNotification';
        yield 'duplicate cron action' => [$config, 'each cron action can only be configured once'];
    }

    private static function getValidConfig(): array
    {
        return [
            [
                'action' => 'sendNotification',
                'expression' => '* * * * *',
            ],
            [
                'action' => 'importData',
                'expression' => '* * * * *',
            ],
        ];
    }
}
