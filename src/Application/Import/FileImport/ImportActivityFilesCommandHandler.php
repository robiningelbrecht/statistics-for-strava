<?php

declare(strict_types=1);

namespace App\Application\Import\FileImport;

use App\Application\Import\FileImport\Pipeline\ActivityFileImportPipeline;
use App\Application\Import\FileImport\Pipeline\ActivityImportContext;
use App\Application\Import\FileImport\Pipeline\SkipActivityFileImport;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Activity\Lap\ActivityLapRepository;
use App\Domain\Activity\Stream\ActivityStreamRepository;
use App\Domain\Import\FileImport;
use App\Domain\Import\FileImportId;
use App\Domain\Import\FileImportRepository;
use App\Domain\Import\FileImportStatus;
use App\Domain\Import\FileParser\CouldNotParseActivityFile;
use App\Domain\Import\FileParser\RawActivityFile;
use App\Domain\Import\FileParser\UnsupportedFileType;
use App\Domain\Import\WatchDirectory;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use App\Infrastructure\Time\Clock\Clock;

final readonly class ImportActivityFilesCommandHandler implements CommandHandler
{
    public function __construct(
        private WatchDirectory $watchDirectory,
        private ActivityFileImportPipeline $pipeline,
        private ActivityRepository $activityRepository,
        private ActivityStreamRepository $activityStreamRepository,
        private ActivityLapRepository $activityLapRepository,
        private FileImportRepository $fileImportRepository,
        private Clock $clock,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof ImportActivityFiles);
        $output = $command->getOutput();
        $output->writeln('Importing activity files...');

        if (!$this->watchDirectory->exists()) {
            $output->writeln('  => No "watch" directory found, nothing to import');

            return;
        }

        $countImported = 0;
        $countSkipped = 0;
        $countFailed = 0;

        foreach ($this->watchDirectory->listFiles() as $item) {
            $filePath = $this->watchDirectory->getAbsolutePathFor($item);
            $context = ActivityImportContext::create($filePath);

            try {
                $context = $this->pipeline->process($context);
            } catch (SkipActivityFileImport) {
                $output->writeln(sprintf('  => Skipping "%s", file was already imported', $filePath->getFilename()));
                ++$countSkipped;
                continue;
            } catch (UnsupportedFileType) {
                $output->writeln(sprintf('  => Skipping "%s", unsupported file type', $filePath->getFilename()));
                ++$countSkipped;
                continue;
            } catch (CouldNotParseActivityFile $e) {
                $this->fileImportRepository->add(FileImport::create(
                    fileImportId: FileImportId::random(),
                    file: $e->getActivityFile(),
                    source: $context->getImportSource(),
                    status: FileImportStatus::FAILED,
                    errorMessage: $e->getMessage(),
                    activityId: null,
                    importedOn: $this->clock->getCurrentDateTimeImmutable(),
                ));
                $output->writeln(sprintf('  => <error>Could not import "%s": %s</error>', $filePath->getFilename(), $e->getMessage()));
                ++$countFailed;
                continue;
            }

            $activity = $context->getActivity() ?? throw new \RuntimeException('Activity not set on $context');
            $activityId = $activity->getId();

            $this->activityRepository->add(ActivityWithRawData::fromState(
                activity: $activity,
                rawData: [],
            ));

            foreach ($context->getStreams() as $stream) {
                $this->activityStreamRepository->add($stream);
            }
            $this->activityRepository->markActivityStreamsAsImported($activityId);

            foreach ($context->getLaps() as $lap) {
                $this->activityLapRepository->add($lap);
            }

            $file = $context->getFile();
            assert($file instanceof RawActivityFile);

            $this->fileImportRepository->add(FileImport::create(
                fileImportId: FileImportId::random(),
                file: $file,
                source: $activity->getImportSource(),
                status: FileImportStatus::SUCCESS,
                errorMessage: null,
                activityId: $activityId,
                importedOn: $this->clock->getCurrentDateTimeImmutable(),
            ));

            $this->watchDirectory->deleteFile($file);

            $output->writeln(sprintf(
                '  => Imported "%s" as activity "%s - %s"',
                $filePath->getFilename(),
                $activity->getName(),
                $activity->getStartDate()->format('d-m-Y'),
            ));
            ++$countImported;
        }

        $output->writeln(sprintf(
            '  => Imported %d, skipped %d, failed %d activity file(s)',
            $countImported,
            $countSkipped,
            $countFailed,
        ));
    }
}
