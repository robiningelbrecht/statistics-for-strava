<?php

declare(strict_types=1);

namespace App\Infrastructure\CQRS;

enum HandlerBuilderType: string
{
    case COMMAND_HANDLER = 'CommandHandler';
    case QUERY_HANDLER = 'QueryHandler';
}
