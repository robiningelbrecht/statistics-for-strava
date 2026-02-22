<?php

declare(strict_types=1);

namespace App\Infrastructure\Time\ResourceUsage;

interface ResourceUsage
{
    public function startTimer(): void;

    public function stopTimer(): void;

    public function getRunTimeInSeconds(): float;

    public function format(): string;
}
