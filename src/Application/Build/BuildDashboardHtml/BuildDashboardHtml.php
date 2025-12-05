<?php

declare(strict_types=1);

namespace App\Application\Build\BuildDashboardHtml;

use App\Infrastructure\CQRS\Command\DomainCommand;

final readonly class BuildDashboardHtml extends DomainCommand
{
    public function __construct(
    ) {
    }
}
