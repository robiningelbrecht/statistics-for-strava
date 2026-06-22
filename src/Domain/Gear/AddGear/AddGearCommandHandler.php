<?php

declare(strict_types=1);

namespace App\Domain\Gear\AddGear;

use App\Domain\Gear\Gear;
use App\Domain\Gear\GearId;
use App\Domain\Gear\GearRepository;
use App\Domain\Gear\GearType;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use App\Infrastructure\Time\Clock\Clock;
use Money\Money;

final readonly class AddGearCommandHandler implements CommandHandler
{
    public function __construct(
        private GearRepository $gearRepository,
        private Clock $clock,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof AddGear);

        $gear = Gear::create(
            gearId: GearId::random(),
            createdOn: $this->clock->getCurrentDateTimeImmutable(),
            name: $command->getName(),
            isRetired: $command->isRetired(),
            type: GearType::CUSTOM,
        );

        if ($command->getPurchasePrice() instanceof Money) {
            $gear = $gear->withPurchasePrice($command->getPurchasePrice());
        }

        $this->gearRepository->add($gear);
    }
}
