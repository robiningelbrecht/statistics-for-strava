<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

final readonly class Pagination
{
    private function __construct(
        private int $offset = 0,
        private int $limit = 10)
    {
        if ($this->limit < 1) {
            throw new \InvalidArgumentException('Invalid limit: '.$this->limit);
        }
    }

    public static function fromOffsetAndLimit(int $offset, int $limit): Pagination
    {
        return new self($offset, $limit);
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function next(): Pagination
    {
        return new self($this->offset + $this->limit, $this->limit);
    }
}
