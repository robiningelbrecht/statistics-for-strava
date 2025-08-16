<?php

namespace App\Domain\Activity\Stream\ImportActivityStreams;

use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawDataRepository;
use App\Domain\Activity\Stream\ActivityStream;
use App\Domain\Activity\Stream\ActivityStreamRepository;
use App\Domain\Activity\Stream\StreamType;
use App\Domain\Strava\Strava;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use App\Infrastructure\Time\Clock\Clock;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;

final readonly class ImportActivityStreamsCommandHandler implements CommandHandler
{
    public function __construct(
        private Strava $strava,
        private ActivityRepository $activityRepository,
        private ActivityWithRawDataRepository $activityWithRawDataRepository,
        private ActivityStreamRepository $activityStreamRepository,
        private Clock $clock,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof ImportActivityStreams);
        $command->getOutput()->writeln('Importing activity streams...');

        foreach ($this->activityRepository->findActivityIdsThatNeedStreamImport() as $activityId) {
            $stravaStreams = [];
            try {
                $stravaStreams = $this->strava->getAllActivityStreams($activityId);
            } catch (ClientException|RequestException $exception) {
                if (!$exception->getResponse()) {
                    // Re-throw, we only want to catch supported error codes.
                    throw $exception;
                }

                if (429 === $exception->getResponse()->getStatusCode()) {
                    // This will allow initial imports with a lot of activities to proceed the next day.
                    // This occurs when we exceed Strava API rate limits or throws an unexpected error.
                    $command->getOutput()->writeln('<error>You probably reached Strava API rate limits. You will need to import the rest of your activity streams tomorrow</error>');
                    break;
                }

                if (404 !== $exception->getResponse()->getStatusCode()) {
                    $command->getOutput()->writeln(sprintf('<error>Strava API threw error: %s</error>', $exception->getMessage()));
                    break;
                }
            }

            foreach ($stravaStreams as $stravaStream) {
                if (!$streamType = StreamType::tryFrom($stravaStream['type'])) {
                    continue;
                }

                if ($this->activityStreamRepository->hasOneForActivityAndStreamType($activityId, $streamType)) {
                    continue;
                }

                $stream = ActivityStream::create(
                    activityId: $activityId,
                    streamType: $streamType,
                    streamData: $stravaStream['data'],
                    createdOn: $this->clock->getCurrentDateTimeImmutable(),
                );
                $this->activityStreamRepository->add($stream);
                $command->getOutput()->writeln(sprintf('  => Imported activity stream "%s"', $stream->getName()));
            }
            $this->activityWithRawDataRepository->markActivityStreamsAsImported($activityId);
        }
    }
}
