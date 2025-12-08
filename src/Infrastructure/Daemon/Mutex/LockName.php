<?php

namespace App\Infrastructure\Daemon\Mutex;

enum LockName: string
{
    case IMPORT_DATA_OR_BUILD_APP = 'importDataOrBuildApp';

    public function key(): string
    {
        return 'lock.'.$this->value;
    }
}
