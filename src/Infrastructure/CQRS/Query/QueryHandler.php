<?php

declare(strict_types=1);

namespace App\Infrastructure\CQRS\Query;

interface QueryHandler
{
    public function handle(Query $query): Response;
}
