<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

final readonly class Pagination
{
    private function __construct(
        private int $offset,
        private int $limit)
    {
        if ($this->limit < 1) {
            throw new \InvalidArgumentException('Invalid limit: '.$this->limit);
        }
        if ($this->offset < 0) {
            throw new \InvalidArgumentException('Invalid offset: '.$this->offset);
        }
    }

    public static function fromOffsetAndLimit(int $offset, int $limit): self
    {
        return new self(
            offset: $offset,
            limit: $limit
        );
    }

    public static function fromPageNumberAndSize(int $pageNumber, int $pageSize): self
    {
        if ($pageNumber <= 0) {
            throw new \InvalidArgumentException(sprintf('page number (%s) should be > 0', $pageNumber));
        }

        return new self(
            offset: ($pageNumber - 1) * $pageSize,
            limit: $pageSize
        );
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function getCurrentPage(): int
    {
        return intdiv($this->offset, $this->limit) + 1;
    }

    public function next(): Pagination
    {
        return new self($this->offset + $this->limit, $this->limit);
    }
}
