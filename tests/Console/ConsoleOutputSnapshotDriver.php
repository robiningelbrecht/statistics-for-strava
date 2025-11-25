<?php

declare(strict_types=1);

namespace App\Tests\Console;

use Spatie\Snapshots\Drivers\TextDriver;

class ConsoleOutputSnapshotDriver extends TextDriver
{
    public function serialize($data): string
    {
        $data = (string) $data
                |> (fn ($str) => str_replace([' ', '-'], '', $str))
                |> (fn ($str) => preg_replace('~\S*/([^/]+\.yaml)~', '$1', $str));

        return parent::serialize($data);
    }
}
