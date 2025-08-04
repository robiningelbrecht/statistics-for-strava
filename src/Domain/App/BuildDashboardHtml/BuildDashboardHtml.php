<?php

declare(strict_types=1);

namespace App\Domain\App\BuildDashboardHtml;

use App\Infrastructure\CQRS\Command\DomainCommand;

final readonly class BuildDashboardHtml extends DomainCommand
{
    public function __construct(
    ) {
    }
}
