<?php

declare(strict_types=1);

namespace App\Domain\Rewind\FindAvailableRewindOptions;

use App\Infrastructure\CQRS\Query\Response;
use App\Infrastructure\ValueObject\Time\Years;

final readonly class FindAvailableRewindOptionsResponse implements Response
{
    public function __construct(
        /** @var array<string, Years> */
        private array $availableRewindOptions,
    ) {
    }

    /**
     * @return string[]
     */
    public function getAvailableOptions(): array
    {
        return array_map(strval(...), array_keys($this->availableRewindOptions));
    }

    public function getYearsToQuery(string $option): Years
    {
        return $this->availableRewindOptions[$option];
    }
}
