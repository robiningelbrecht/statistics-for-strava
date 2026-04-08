<?php

declare(strict_types=1);

namespace App\Infrastructure\Mutex;

enum LockName: string
{
    case IMPORT_DATA_OR_BUILD_APP = 'importDataOrBuildApp';

    public function key(): string
    {
        return 'lock.'.$this->value;
    }
}
