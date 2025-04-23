<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\CQRS\Query\Bus\FindSomethingThrowHandlerFailedException;

use App\Infrastructure\CQRS\Query\Query;
use App\Infrastructure\CQRS\Query\QueryHandler;
use App\Infrastructure\CQRS\Query\Response;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

final readonly class FindSomethingThrowHandlerFailedExceptionQueryHandler implements QueryHandler
{
    public function handle(Query $query): Response
    {
        throw new HandlerFailedException(new Envelope(new \stdClass(), []), [new \RuntimeException()]);
    }
}
