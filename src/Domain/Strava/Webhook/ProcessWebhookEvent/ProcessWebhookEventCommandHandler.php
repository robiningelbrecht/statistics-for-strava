<?php

declare(strict_types=1);

namespace App\Domain\Strava\Webhook\ProcessWebhookEvent;

use App\Domain\Strava\Webhook\WebhookEvent;
use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

final readonly class ProcessWebhookEventCommandHandler implements CommandHandler
{
    public function __construct(
        private LoggerInterface $logger,
        private string $projectDir,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof ProcessWebhookEvent);

        $event = WebhookEvent::fromWebhookPayload($command->getEventPayload());

        $this->logger->info('Processing Strava webhook event', [
            'object_type' => $event->getObjectType(),
            'object_id' => $event->getObjectId(),
            'aspect_type' => $event->getAspectType(),
            'owner_id' => $event->getOwnerId(),
        ]);

        // Only process activity create/update events
        if (!$event->shouldTriggerImport()) {
            $this->logger->info('Skipping event - not an activity create/update', [
                'object_type' => $event->getObjectType(),
                'aspect_type' => $event->getAspectType(),
            ]);

            return;
        }

        // Run import and build in background to avoid webhook timeout
        $this->runImportAndBuildInBackground();
    }

    private function runImportAndBuildInBackground(): void
    {
        $phpBinaryFinder = new PhpExecutableFinder();
        $phpBinaryPath = $phpBinaryFinder->find();

        if (!$phpBinaryPath) {
            $this->logger->error('PHP binary not found, cannot run import in background');

            return;
        }

        $consolePath = $this->projectDir.'/bin/console';

        // Create a shell script to run both commands sequentially
        $script = sprintf(
            '%s %s app:strava:import-data && %s %s app:strava:build-files',
            $phpBinaryPath,
            $consolePath,
            $phpBinaryPath,
            $consolePath
        );

        // Run in background (non-blocking)
        $process = Process::fromShellCommandline($script);
        $process->setTimeout(null); // No timeout
        $process->start();

        $this->logger->info('Started import and build process in background', [
            'pid' => $process->getPid(),
        ]);
    }
}
