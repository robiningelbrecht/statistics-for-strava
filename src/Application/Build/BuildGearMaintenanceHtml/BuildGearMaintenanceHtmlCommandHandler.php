<?php

declare(strict_types=1);

namespace App\Application\Build\BuildGearMaintenanceHtml;

use App\Domain\Gear\Gear;
use App\Domain\Gear\GearId;
use App\Domain\Gear\GearIds;
use App\Domain\Gear\GearRepository;
use App\Domain\Gear\Gears;
use App\Domain\Gear\Maintenance\GearComponent;
use App\Domain\Gear\Maintenance\GearMaintenanceRepository;
use App\Domain\Gear\Maintenance\Task\Progress\MaintenanceTaskProgressCalculator;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use League\Flysystem\FilesystemOperator;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final readonly class BuildGearMaintenanceHtmlCommandHandler implements CommandHandler
{
    public function __construct(
        private GearMaintenanceRepository $gearMaintenanceRepository,
        private GearRepository $gearRepository,
        private MaintenanceTaskProgressCalculator $maintenanceTaskProgressCalculator,
        private Environment $twig,
        private FilesystemOperator $buildHtmlStorage,
        private TranslatorInterface $translator,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof BuildGearMaintenanceHtml);

        $gearMaintenanceConfig = $this->gearMaintenanceRepository->find();
        $gears = $this->gearRepository->findAll();

        if (!$gearMaintenanceConfig->isFeatureEnabled()) {
            $this->buildHtmlStorage->write(
                'gear/maintenance.html',
                $this->twig->load('html/gear/maintenance/gear-maintenance-disabled.html.twig')->render()
            );

            return;
        }

        // Validate that all gear ids are in the DB.
        $gearIdsInDb = GearIds::fromArray($gears->map(fn (Gear $gear): GearId => $gear->getId()));
        $gearIdsInConfig = $gearMaintenanceConfig->getAllReferencedGearIds();

        $errors = [];
        /** @var GearId $gearIdInConfig */
        foreach ($gearIdsInConfig as $gearIdInConfig) {
            if ($gearIdsInDb->has($gearIdInConfig)) {
                continue;
            }

            $errors[] = $this->translator->trans(
                'Gear "{gearId}" is referenced in your maintenance config file, but was not imported from Strava. Please check that the gear exists and is correctly synced.',
                ['{gearId}' => $gearIdInConfig->toUnprefixedString()]
            );
        }

        $gearsThatAreAttachedToComponents = Gears::empty();
        $gearIdsThatAreAttachedToComponents = [];
        /** @var GearComponent $gearComponent */
        foreach ($gearMaintenanceConfig->getGearComponents() as $gearComponent) {
            foreach ($gearComponent->getAttachedTo() as $attachedToGearId) {
                if (!($gear = $gears->getByGearId($attachedToGearId)) instanceof Gear) {
                    continue;
                }
                if (in_array((string) $gear->getId(), $gearIdsThatAreAttachedToComponents)) {
                    continue;
                }
                if ($gear->isRetired() && $gearMaintenanceConfig->ignoreRetiredGear()) {
                    continue;
                }

                $gearsThatAreAttachedToComponents->add($gear);
                $gearIdsThatAreAttachedToComponents[] = (string) $gear->getId();
            }
        }

        if ($gearsThatAreAttachedToComponents->isEmpty()) {
            $errors[] = $this->translator->trans('It looks like no valid gear is attached to any of the components. Please check your config file.');
        }

        $this->buildHtmlStorage->write(
            'gear/maintenance.html',
            $this->twig->load('html/gear/maintenance/gear-maintenance.html.twig')->render([
                'errors' => $errors,
                'gearsAttachedToComponents' => $gearsThatAreAttachedToComponents,
                'gearComponents' => $gearMaintenanceConfig->getGearComponents(),
                'gearIdsThatHaveDueTasks' => $this->maintenanceTaskProgressCalculator->getGearIdsThatHaveDueTasks(),
            ])
        );
    }
}
