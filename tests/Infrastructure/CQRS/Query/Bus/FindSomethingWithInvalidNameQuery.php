<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\CQRS\Query\Bus;

use App\Infrastructure\CQRS\Query\Query;
use App\Infrastructure\CQRS\Query\QueryHandler;
use App\Infrastructure\CQRS\Query\Response;
use App\Tests\Infrastructure\CQRS\Query\Bus\FindSomething\FindSomethingResponse;

class FindSomethingWithInvalidNameQuery implements QueryHandler
{
    public function handle(Query $query): Response
    {
        return new FindSomethingResponse();
    }
}
