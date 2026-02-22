<?php

namespace App\Infrastructure\Time\Sleep;

interface Sleep
{
    public function sweetDreams(int $durationInSeconds): void;
}
