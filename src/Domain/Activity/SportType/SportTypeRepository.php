<?php

declare(strict_types=1);

namespace App\Domain\Activity\SportType;

interface SportTypeRepository
{
    public function findAll(): SportTypes;
}
