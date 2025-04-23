<?php

declare(strict_types=1);

namespace App\Domain\App\BuildGearMaintenanceHtml;

use App\Domain\Strava\Gear\Gear;
use App\Domain\Strava\Gear\GearIds;
use App\Domain\Strava\Gear\GearRepository;
use App\Domain\Strava\Gear\Gears;
use App\Domain\Strava\Gear\Maintenance\GearMaintenanceConfig;
use App\Domain\Strava\Gear\Maintenance\Task\MaintenanceTaskTagRepository;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
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
        $gearIdsInDb = GearIds::fromArray($gears->map(fn (Gear $gear) => $gear->getId()));
        // By default, gear ids are prefixed with "b" or "g" in the Strava API.
        // But these prefixes are not exposed in the URL of the gear, so users might
        // copy-paste the gear id from the URL without these prefixes.
        // We need to account for this.
        $this->gearMaintenanceConfig->normalizeGearIds($gearIdsInDb);
        $gearIdsInConfig = $this->gearMaintenanceConfig->getAllReferencedGearIds();

        $errors = [];
        /** @var \App\Domain\Strava\Gear\GearId $gearIdInConfig */
        foreach ($gearIdsInConfig as $gearIdInConfig) {
            if ($gearIdsInDb->has($gearIdInConfig)) {
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

        // Check if there are any invalid tags.
        $maintenanceTaskTags = $this->maintenanceTaskTagRepository->findAll();
        /** @var \App\Domain\Strava\Gear\Maintenance\Task\MaintenanceTaskTag $maintenanceTaskTag */
        foreach ($maintenanceTaskTags->filterOnInvalid() as $maintenanceTaskTag) {
            $warnings[] = $this->translator->trans(
                'Tag "{maintenanceTaskTag}" was used on "{activityName}", but the gear referenced on that activity is not attached to this component.',
                [
                    '{maintenanceTaskTag}' => $maintenanceTaskTag->getTag(),
                    '{activityName}' => $maintenanceTaskTag->getActivityName(),
                ]
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

                if (($imageSrc = $this->gearMaintenanceConfig->getImageReferenceForGear($gear->getId()))
                    && $this->gearMaintenanceStorage->fileExists($imageSrc)) {
                    $gear->enrichWithImageSrc('/gear-maintenance/'.$imageSrc);
                }

                $gearsAttachedToComponents->add($gear);
            }
        }

        if ($gearsAttachedToComponents->isEmpty()) {
            $errors[] = $this->translator->trans('It looks like no valid gear is attached to any of the components. Please check your config file.');
        }

        $this->buildStorage->write(
            'gear-maintenance.html',
            $this->twig->load('html/gear/gear-maintenance.html.twig')->render([
                'errors' => $errors,
                'warnings' => $warnings,
                'gearsAttachedToComponents' => $gearsAttachedToComponents,
                'gearComponents' => $this->gearMaintenanceConfig->getGearComponents(),
                'maintenanceTaskTags' => $maintenanceTaskTags->filterOnValid(),
            ])
        );
    }
}
