<?php

declare(strict_types=1);

namespace App\Domain\Activity;

interface ActivityTypeRepository
{
    public function findAll(): ActivityTypes;
}
