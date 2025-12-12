<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueObject;

final readonly class DataTableRow implements \JsonSerializable
{
    private function __construct(
        private string $markup,
        /** @var string[] */
        private array $searchables,
        /** @var array<string, string|int|string[]> */
        private array $filterables,
        /** @var array<string, string|int|float> */
        private array $sortValues,
        /** @var array<string, string|int|float> */
        private array $summables,
    ) {
    }

    /**
     * @param string[]                           $searchables
     * @param array<string, string|int|string[]> $filterables
     * @param array<string, string|int|float>    $sortValues
     * @param array<string, string|int|float>    $summables
     */
    public static function create(
        string $markup,
        array $searchables,
        array $filterables,
        array $sortValues,
        array $summables,
    ): self {
        return new self(
            markup: $markup,
            searchables: $searchables,
            filterables: $filterables,
            sortValues: $sortValues,
            summables: $summables,
        );
    }

    public function getMarkup(): string
    {
        return $this->markup;
    }

    /**
     * @return string[]
     */
    public function getSearchables(): array
    {
        return $this->searchables;
    }

    /**
     * @return array<string, string|int|float>
     */
    public function getSortValues(): array
    {
        return $this->sortValues;
    }

    /**
     * @return array<string, string|int|string[]>
     */
    public function getFilterables(): array
    {
        return $this->filterables;
    }

    /**
     * @return array<string, string|int|float>
     */
    public function getSummables(): array
    {
        return $this->summables;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'active' => true,
            'searchables' => implode(' ', $this->getSearchables()),
            'filterables' => $this->getFilterables(),
            'summables' => $this->getSummables(),
            'sort' => $this->getSortValues(),
            'markup' => $this->getMarkup(),
        ];
    }
}
