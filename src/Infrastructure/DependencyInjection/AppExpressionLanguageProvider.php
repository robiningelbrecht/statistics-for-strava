<?php

declare(strict_types=1);

namespace App\Infrastructure\DependencyInjection;

use App\Infrastructure\Config\AppConfig;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

final readonly class AppExpressionLanguageProvider implements ExpressionFunctionProviderInterface
{
    /**
     * @return ExpressionFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new ExpressionFunction(
                name: 'app_config',
                compiler: fn (string $appConfigKey, mixed $defaultValue = null): string => sprintf('$container->get(\App\Infrastructure\Config\AppConfig::class)->get(%s, %s)', $appConfigKey, $defaultValue),
                evaluator: fn (array $variables, string $appConfigKey, mixed $defaultValue = null): mixed => $variables['container']->get(AppConfig::class)->get($appConfigKey, $defaultValue)
            ),
        ];
    }
}
