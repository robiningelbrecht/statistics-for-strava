<?php

declare(strict_types=1);

namespace App\Domain\App\BuildGearMaintenanceHtml;

use App\Domain\Strava\Gear\Gear;
use App\Domain\Strava\Gear\GearRepository;
use App\Domain\Strava\Gear\Gears;
use App\Domain\Strava\Gear\Maintenance\GearMaintenanceConfig;
use App\Domain\Strava\Gear\Maintenance\Task\MaintenanceTaskTagRepository;
use App\Infrastructure\CQRS\Command;
use App\Infrastructure\CQRS\CommandHandler;
use League\Flysystem\FilesystemOperator;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final readonly class BuildGearMaintenanceHtmlCommandHandler implements CommandHandler
{
    public function __construct(
        private GearMaintenanceConfig $gearMaintenanceConfig,
        private MaintenanceTaskTagRepository $maintenanceTaskTagRepository,
        private GearRepository $gearRepository,
        private FilesystemOperator $gearMaintenanceStorage,
        private Environment $twig,
        private FilesystemOperator $buildStorage,
        private TranslatorInterface $translator,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof BuildGearMaintenanceHtml);
        if (!$this->gearMaintenanceConfig->isFeatureEnabled()) {
            $this->buildStorage->write(
                'gear-maintenance.html',
                $this->twig->load('html/gear/gear-maintenance-disabled.html.twig')->render()
            );

            return;
        }

        $gears = $this->gearRepository->findAll();

        // Validate that all gear ids are in the DB.
        $gearIdsInDb = $gears->map(fn (Gear $gear) => $gear->getId());
        $gearIdsInConfig = $this->gearMaintenanceConfig->getAllReferencedGearIds();
        $errors = [];
        /** @var \App\Domain\Strava\Gear\GearId $gearIdInConfig */
        foreach ($gearIdsInConfig as $gearIdInConfig) {
            if (in_array($gearIdInConfig, $gearIdsInDb)) {
                continue;
            }

            $errors[] = $this->translator->trans(
                'Gear "{gearId}" is referenced in your maintenance config file, but was not imported from Strava. Please check that the gear exists and is correctly synced.',
                ['{gearId}' => $gearIdInConfig->toUnprefixedString()]
            );
        }
        // Check if referenced images exist.
        $warnings = [];
        foreach ($this->gearMaintenanceConfig->getAllReferencedImages() as $imageSrc) {
            if ($this->gearMaintenanceStorage->fileExists($imageSrc)) {
                continue;
            }
            $warnings[] = $this->translator->trans(
                'Image "{imgSrc}" is referenced in your maintenance config file, but was not found in "storage/gear-maintenance"',
                ['{imgSrc}' => $imageSrc]
            );
        }

        $gearsAttachedToComponents = Gears::empty();
        /** @var \App\Domain\Strava\Gear\Maintenance\GearComponent $gearComponent */
        foreach ($this->gearMaintenanceConfig->getGearComponents() as $gearComponent) {
            foreach ($gearComponent->getAttachedTo() as $attachedToGearId) {
                if (!$gear = $gears->getByGearId($attachedToGearId)) {
                    continue;
                }
                if ($gearsAttachedToComponents->has($gear)) {
                    continue;
                }
                $gearsAttachedToComponents->add($gear);
            }
        }

        $this->buildStorage->write(
            'gear-maintenance.html',
            $this->twig->load('html/gear/gear-maintenance.html.twig')->render([
                'errors' => $errors,
                'warnings' => $warnings,
                'gearsAttachedToComponents' => $gearsAttachedToComponents,
                'gearComponents' => $this->gearMaintenanceConfig->getGearComponents(),
                'maintenanceTaskTags' => $this->maintenanceTaskTagRepository->findAll(),
            ])
        );
    }
}
