<?php

declare(strict_types=1);

namespace App\Infrastructure\CQRS;

use App\Infrastructure\CQRS\Command\CommandHandler;
use App\Infrastructure\CQRS\Query\QueryHandler;

final readonly class HandlerBuilder
{
    private string $disallowedObjectSuffix;
    private string $handlerSuffix;

    public function __construct(
        private HandlerBuilderType $handlerBuilderType,
    ) {
        $this->handlerSuffix = $this->handlerBuilderType->value;
        $this->disallowedObjectSuffix = str_replace('Handler', '', $this->handlerSuffix);
    }

    /**
     * @param iterable<CommandHandler|QueryHandler> $handlers
     *
     * @return array<string, non-empty-list<\Closure>>
     */
    public function fromCallables(iterable $handlers): array
    {
        $registeredHandlers = [];

        foreach ($handlers as $handler) {
            if (!str_ends_with($handler::class, $this->handlerSuffix)) {
                throw new CanNotRegisterCQRSHandler(sprintf('Fqcn "%s" does not end with "%s"', $handler::class, $this->handlerSuffix));
            }

            $handleParamFqcn = str_replace($this->handlerSuffix, '', $handler::class);
            if (!class_exists($handleParamFqcn)) {
                throw new CanNotRegisterCQRSHandler(sprintf('No corresponding object for %s "%s" found. Expected namespace: %s', $this->handlerSuffix, $handler::class, $handleParamFqcn));
            }
            if (str_ends_with($handleParamFqcn, $this->disallowedObjectSuffix)) {
                throw new CanNotRegisterCQRSHandler(sprintf('Object name cannot end with "%s"', $this->disallowedObjectSuffix));
            }

            $handleParamFqcn = str_replace($this->handlerSuffix, '', $handler::class);
            $registeredHandlers[$handleParamFqcn][] = $handler->handle(...);
        }

        return $registeredHandlers;
    }
}
