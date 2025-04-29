<?php

namespace App\Tests\Infrastructure\DependencyInjection;

use App\Infrastructure\DependencyInjection\FallbackEnvVarProcessor;
use PHPUnit\Framework\TestCase;

class FallbackEnvVarProcessorTest extends TestCase
{
    public function testGetEnvWithOneFallback(): void
    {
        $processor = new FallbackEnvVarProcessor();

        $this->assertEquals(
            'value1',
            $processor->getEnv('prefix', 'value1', fn (mixed $value) => $value),
        );
    }

    public function testGetEnvWithMultipleFallback(): void
    {
        $processor = new FallbackEnvVarProcessor();

        $this->assertEquals(
            'value2',
            $processor->getEnv('prefix', 'value1:value2:value3', fn (mixed $value) => 'value2' === $value ? $value : null),
        );
    }

    public function testGetProvidedTypes(): void
    {
        $this->assertEquals(
            [
                'fallback' => 'bool|int|float|string|array',
            ],
            FallbackEnvVarProcessor::getProvidedTypes(),
        );
    }
}
