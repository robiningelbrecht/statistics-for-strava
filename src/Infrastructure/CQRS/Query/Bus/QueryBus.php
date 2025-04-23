<?php

declare(strict_types=1);

namespace App\Infrastructure\CQRS\Query\Bus;

use App\Infrastructure\CQRS\Query\Query;
use App\Infrastructure\CQRS\Query\Response;

interface QueryBus
{
    public function ask(Query $query): Response;
}
