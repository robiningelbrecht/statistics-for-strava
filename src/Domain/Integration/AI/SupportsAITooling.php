<?php

declare(strict_types=1);

namespace App\Domain\Integration\AI;

interface SupportsAITooling
{
    /**
     * @return array<string|int, mixed>
     */
    public function exportForAITooling(): array;
}
