<?php

declare(strict_types=1);

namespace App\Infrastructure\CQRS\Query;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.query_handler')]
interface QueryHandler
{
    public function handle(Query $query): Response;
}
