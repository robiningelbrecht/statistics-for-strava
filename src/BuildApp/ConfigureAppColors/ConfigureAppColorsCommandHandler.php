<?php

declare(strict_types=1);

namespace App\BuildApp\ConfigureAppColors;

use App\Domain\Activity\ActivityTypeRepository;
use App\Domain\Activity\SportType\SportTypeRepository;
use App\Domain\Gear\GearRepository;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use App\Infrastructure\Theme\ChartColors;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\VarExporter\VarExporter;

final readonly class ConfigureAppColorsCommandHandler implements CommandHandler
{
    public function __construct(
        private SportTypeRepository $sportTypeRepository,
        private ActivityTypeRepository $activityTypeRepository,
        private GearRepository $gearRepository,
        private FilesystemOperator $defaultStorage,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof ConfigureAppColors);

        $configuredColors = [];
        $defaultChatColors = ChartColors::default();

        $sportTypes = $this->sportTypeRepository->findAll();
        foreach ($sportTypes as $index => $sportType) {
            $configuredColors['sportType'][$sportType->value] = $defaultChatColors[$index % count($defaultChatColors)];
        }

        $activityTypes = $this->activityTypeRepository->findAll();
        foreach ($activityTypes as $index => $activityType) {
            $configuredColors['activityType'][$activityType->value] = $defaultChatColors[$index % count($defaultChatColors)];
        }

        $gears = $this->gearRepository->findAll();
        foreach ($gears as $index => $gear) {
            $configuredColors['gear'][(string) $gear->getId()] = $defaultChatColors[$index % count($defaultChatColors)];
        }

        $exportedColors = VarExporter::export($configuredColors);
        $fileContent = <<<PHP
<?php

declare(strict_types=1);

return $exportedColors;

PHP;

        $this->defaultStorage->write('config/chart-colors.php', $fileContent);
    }
}
