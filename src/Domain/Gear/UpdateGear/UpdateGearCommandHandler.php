<?php

declare(strict_types=1);

namespace App\Domain\Gear\UpdateGear;

use App\Domain\Gear\GearRepository;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use Money\Money;

final readonly class UpdateGearCommandHandler implements CommandHandler
{
    public function __construct(
        private GearRepository $gearRepository,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof UpdateGear);

        $gear = $this->gearRepository->find($command->getGearId())
            ->withName($command->getName())
            ->withIsRetired($command->isRetired());

        if ($command->getPurchasePrice() instanceof Money) {
            $gear = $gear->withPurchasePrice($command->getPurchasePrice());
        }

        $this->gearRepository->update($gear);
    }
}
