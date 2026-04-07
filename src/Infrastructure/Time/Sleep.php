<?php

declare(strict_types=1);

namespace App\Infrastructure\Time;

interface Sleep
{
    public function sweetDreams(int $durationInSeconds): void;
}
