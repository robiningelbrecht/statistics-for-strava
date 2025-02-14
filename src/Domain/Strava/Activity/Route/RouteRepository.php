<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Route;

interface RouteRepository
{
    public function findAll(): Routes;
}
