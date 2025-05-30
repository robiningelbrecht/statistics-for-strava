<?php

declare(strict_types=1);

namespace App\Domain\Strava\Gear\CustomGear;

use App\Domain\Strava\Gear\ImportedGear\ImportedGear;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
final class CustomGear extends ImportedGear
{
}
