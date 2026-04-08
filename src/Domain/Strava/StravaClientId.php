<?php

declare(strict_types=1);

namespace App\Domain\Strava;

use App\Infrastructure\ValueObject\String\NonEmptyStringLiteral;

final readonly class StravaClientId extends NonEmptyStringLiteral
{
}
