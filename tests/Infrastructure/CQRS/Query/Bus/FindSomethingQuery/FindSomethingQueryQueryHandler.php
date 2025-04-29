<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\CQRS\Query\Bus\FindSomethingQuery;

use App\Infrastructure\CQRS\Query\Query;
use App\Infrastructure\CQRS\Query\QueryHandler;
use App\Infrastructure\CQRS\Query\Response;
use App\Tests\Infrastructure\CQRS\Query\Bus\FindSomething\FindSomethingResponse;

final readonly class FindSomethingQueryQueryHandler implements QueryHandler
{
    public function handle(Query $query): Response
    {
        return new FindSomethingResponse();
    }
}
