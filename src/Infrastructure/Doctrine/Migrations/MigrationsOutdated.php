<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Migrations;

final class MigrationsOutdated extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct(
            'Your database migrations are outdated. Migrations have been squashed and you need to update to v4.4.2 before upgrading this version. '
            .'Please pull an older version of the app, run the import there, then update to this version again.'
        );
    }
}
