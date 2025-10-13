<?php

declare(strict_types=1);

namespace App\Domain\Zwift\FindZwiftStatsPerWorld;

use App\Infrastructure\CQRS\Query\Query;

/**
 * @implements Query<\App\Domain\Zwift\FindZwiftStatsPerWorld\FindZwiftStatsPerWorldResponse>
 */
final readonly class FindZwiftStatsPerWorld implements Query
{
}
