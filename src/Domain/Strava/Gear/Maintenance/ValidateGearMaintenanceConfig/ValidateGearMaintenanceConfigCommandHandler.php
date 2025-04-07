<?php

declare(strict_types=1);

namespace App\Domain\Strava\Gear\Maintenance\ValidateGearMaintenanceConfig;

use App\Domain\Strava\Gear\Gear;
use App\Domain\Strava\Gear\GearRepository;
use App\Domain\Strava\Gear\Maintenance\GearMaintenanceConfig;
use App\Infrastructure\CQRS\Command;
use App\Infrastructure\CQRS\CommandHandler;
use League\Flysystem\FilesystemOperator;

final readonly class ValidateGearMaintenanceConfigCommandHandler implements CommandHandler
{
    public function __construct(
        private GearMaintenanceConfig $gearMaintenanceConfig,
        private GearRepository $gearRepository,
        private FilesystemOperator $gearMaintenanceStorage,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof ValidateGearMaintenanceConfig);
        if (!$this->gearMaintenanceConfig->isFeatureEnabled()) {
            return;
        }

        $command->getOutput()->writeln('Validating gear maintenance config...');

        // Validate that all gear ids are in the DB.
        $gearIdsInDb = $this->gearRepository->findAll()->map(fn (Gear $gear) => $gear->getId());
        $gearIdsInConfig = $this->gearMaintenanceConfig->getAllReferencedGearIds();
        $isValid = true;
        /** @var \App\Domain\Strava\Gear\GearId $gearIdInConfig */
        foreach ($gearIdsInConfig as $gearIdInConfig) {
            if (in_array($gearIdInConfig, $gearIdsInDb)) {
                continue;
            }

            $isValid = false;
            $command->getOutput()->writeln(sprintf(
                '  => ❌ gear "%s" is referenced in your maintenance yaml file, but was not imported from Strava.',
                $gearIdInConfig->toUnprefixedString()
            ));
        }
        // Check if referenced images exist.
        foreach ($this->gearMaintenanceConfig->getAllReferencedImages() as $imageSrc) {
            if ($this->gearMaintenanceStorage->fileExists($imageSrc)) {
                continue;
            }
            $isValid = false;
            $command->getOutput()->writeln(sprintf(
                '  => ❌ image "%s" is referenced in your maintenance yaml file, but was not found in "storage/gear-maintenance".',
                $imageSrc
            ));
        }
        // Check if components got removed from config but are in DB.

        // Check if maintenance tasks got removed from config but are in DB.

        if (!$isValid) {
            return;
        }
        $command->getOutput()->writeln('  => ✅ config is valid');
    }
}
