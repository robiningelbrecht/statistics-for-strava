<?php

declare(strict_types=1);

namespace App\Infrastructure\DependencyInjection;

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
            new ExpressionFunction('app_config', function (string $appConfigKey, mixed $defaultValue = null): string {
                return sprintf('$container->get("app.config")->get(%s, %s)', $appConfigKey, $defaultValue);
            }, function (array $variables, string $appConfigKey, mixed $defaultValue = null): string {
                return $variables['container']->get('app.config')->get($appConfigKey, $defaultValue);
            }),
        ];
    }
}
