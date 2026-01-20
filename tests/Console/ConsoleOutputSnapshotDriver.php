<?php

declare(strict_types=1);

namespace App\Tests\Console;

use Spatie\Snapshots\Drivers\TextDriver;

class ConsoleOutputSnapshotDriver extends TextDriver
{
    #[\Override]
    public function serialize($data): string
    {
        $data = (string) $data
                |> (fn ($str): string => str_replace([' ', '-'], '', $str))
                |> (fn ($str): string => preg_replace('/PHP(\d+\.\d+)\.\d+/', 'PHP$1.x', (string) $str))
                |> (fn ($str): ?string => preg_replace('~\S*/([^/]+\.yaml)~', '$1', (string) $str));

        return parent::serialize($data);
    }
}
