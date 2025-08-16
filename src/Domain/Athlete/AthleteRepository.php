<?php

declare(strict_types=1);

namespace App\Domain\Athlete;

interface AthleteRepository
{
    public function save(Athlete $athlete): void;

    public function find(): Athlete;
}
