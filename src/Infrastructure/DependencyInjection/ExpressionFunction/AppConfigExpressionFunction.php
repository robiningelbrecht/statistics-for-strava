<?php

declare(strict_types=1);

namespace App\Infrastructure\DependencyInjection\ExpressionFunction;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;

final class AppConfigExpressionFunction extends ExpressionFunction
{
    public function __construct(string $name)
    {
        parent::__construct(
            $name,
            $this->compile(...)->bindTo($this),
            $this->evaluate(...)->bindTo($this)
        );
    }

    private function compile(string $appConfigKey): string
    {
        return sprintf('$container->get("app.config")->get(%s)', $appConfigKey);
    }

    /**
     * @param array<string, mixed> $variables
     *
     * @return string|int|float|array<string,mixed>|null
     */
    private function evaluate(array $variables, string $appConfigKey): string|int|float|array|null
    {
        return $variables['container']->get('app.config')->get($appConfigKey);
    }
}
