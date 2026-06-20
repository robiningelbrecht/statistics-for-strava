<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Request;

use App\Infrastructure\Repository\Pagination;
use Symfony\Component\HttpFoundation\Request;

trait PaginationFromRequest
{
    private const int DEFAULT_PAGE_SIZE = 25;
    private const int MAX_PAGE_SIZE = 100;

    private function paginationFromRequest(Request $request): Pagination
    {
        $paginationParams = $request->query->all('pagination');
        $pageNumber = filter_var($paginationParams['page'] ?? 1, FILTER_VALIDATE_INT);
        if (false === $pageNumber) {
            throw new \InvalidArgumentException('page number should be a valid integer');
        }

        $pageSize = filter_var($paginationParams['size'] ?? self::DEFAULT_PAGE_SIZE, FILTER_VALIDATE_INT);
        if (false === $pageSize) {
            throw new \InvalidArgumentException('page size should be a valid integer');
        }

        if ($pageSize > self::MAX_PAGE_SIZE) {
            throw new \InvalidArgumentException("page size ($pageSize) should not exceed ".self::MAX_PAGE_SIZE);
        }

        return Pagination::fromPageNumberAndSize($pageNumber, $pageSize);
    }
}
