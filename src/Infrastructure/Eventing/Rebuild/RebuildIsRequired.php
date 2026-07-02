<?php

declare(strict_types=1);

namespace App\Infrastructure\Eventing\Rebuild;

use App\Infrastructure\Eventing\DomainEvent;

final class RebuildIsRequired extends DomainEvent
{
}
