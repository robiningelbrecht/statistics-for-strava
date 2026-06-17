<?php

declare(strict_types=1);

namespace App\Infrastructure\Config;

use App\Application\AppVersion;

enum FeatureFlag: string
{
    case ADMIN = 'admin';

    public function isEnabled(): bool
    {
        $envVar = sprintf('FEATURE_ENABLE_%s', strtoupper($this->value));

        return AppVersion::isAtLeastVersion5() || filter_var($_SERVER[$envVar] ?? false, FILTER_VALIDATE_BOOL);
    }
}
