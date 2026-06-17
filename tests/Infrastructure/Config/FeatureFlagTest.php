<?php

namespace App\Tests\Infrastructure\Config;

use App\Infrastructure\Config\FeatureFlag;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FeatureFlagTest extends TestCase
{
    private const string ADMIN_ENV_VAR = 'FEATURE_ENABLE_ADMIN';

    private bool $envVarWasSet;
    private mixed $originalEnvVarValue = null;

    #[DataProvider('provideEnvVarValues')]
    public function testItShouldResolveEnabledStateFromEnvVar(string $envVarValue, bool $expectedToBeEnabled): void
    {
        $_SERVER[self::ADMIN_ENV_VAR] = $envVarValue;

        self::assertSame($expectedToBeEnabled, FeatureFlag::ADMIN->isEnabled());
    }

    public function testItShouldBeDisabledWhenEnvVarIsNotSet(): void
    {
        unset($_SERVER[self::ADMIN_ENV_VAR]);

        self::assertFalse(FeatureFlag::ADMIN->isEnabled());
    }

    public static function provideEnvVarValues(): iterable
    {
        yield '"1" is enabled' => ['1', true];
        yield '"true" is enabled' => ['true', true];
        yield '"on" is enabled' => ['on', true];
        yield '"yes" is enabled' => ['yes', true];
        yield '"0" is disabled' => ['0', false];
        yield '"false" is disabled' => ['false', false];
        yield '"off" is disabled' => ['off', false];
        yield 'empty string is disabled' => ['', false];
        yield 'arbitrary string is disabled' => ['nope', false];
    }

    protected function setUp(): void
    {
        $this->envVarWasSet = array_key_exists(self::ADMIN_ENV_VAR, $_SERVER);
        $this->originalEnvVarValue = $_SERVER[self::ADMIN_ENV_VAR] ?? null;
    }

    protected function tearDown(): void
    {
        if ($this->envVarWasSet) {
            $_SERVER[self::ADMIN_ENV_VAR] = $this->originalEnvVarValue;

            return;
        }

        unset($_SERVER[self::ADMIN_ENV_VAR]);
    }
}
