<?php

declare(strict_types=1);

namespace App\Application\Import\ImportSegments;

use App\Domain\Segment\Segment;
use App\Domain\Segment\SegmentRepository;
use App\Domain\Strava\RateLimit\StravaRateLimitHasBeenReached;
use App\Domain\Strava\Strava;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use App\Infrastructure\Daemon\Mutex\LockName;
use App\Infrastructure\Daemon\Mutex\Mutex;
use App\Infrastructure\DependencyInjection\Mutex\WithMutex;
use App\Infrastructure\ValueObject\Geography\EncodedPolyline;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;

#[WithMutex(lockName: LockName::IMPORT_DATA_OR_BUILD_APP)]
final readonly class ImportSegmentsCommandHandler implements CommandHandler
{
    public function __construct(
        private SegmentRepository $segmentRepository,
        private OptInToSegmentDetailsImport $optInToSegmentDetailsImport,
        private Strava $strava,
        private Mutex $mutex,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof ImportSegments);

        if (!$this->optInToSegmentDetailsImport->hasOptedIn()) {
            return;
        }
        $this->strava->setConsoleOutput($command->getOutput());

        $command->getOutput()->writeln('Importing segments...');

        $segmentIdsMissingDetails = $this->segmentRepository->findSegmentsIdsMissingDetails();
        $numberOfSegmentIdsMissingDetails = count($segmentIdsMissingDetails);
        $delta = 1;
        foreach ($segmentIdsMissingDetails as $segmentId) {
            $segment = $this->segmentRepository->find($segmentId);
            try {
                $stravaSegment = $this->strava->getSegment($segmentId);
                $this->mutex->heartbeat();

                $segment->updatePolyline(
                    EncodedPolyline::fromOptionalString(
                        $stravaSegment['map']['polyline'] ?? null
                    )
                );
                $segment->flagDetailsAsImported();
                $this->segmentRepository->update($segment);

                $command->getOutput()->writeln(
                    sprintf(
                        '  => [%d/%d] Imported segment details: "%s"',
                        $delta,
                        $numberOfSegmentIdsMissingDetails,
                        $segment->getName()
                    )
                );

                ++$delta;
            } catch (StravaRateLimitHasBeenReached $exception) {
                $command->getOutput()->writeln(sprintf('<error>%s</error>', $exception->getMessage()));

                return;
            } catch (ClientException|RequestException $exception) {
                if (404 === $exception->getResponse()?->getStatusCode()) {
                    // Segment does not exist anymore. Mark as imported.
                    $segment->flagDetailsAsImported();
                    $this->segmentRepository->update($segment);
                }

                $command->getOutput()->writeln(sprintf('<error>Strava API threw error: %s</error>', $exception->getMessage()));
                continue;
            }
        }
    }
}
