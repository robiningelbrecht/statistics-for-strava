<?php

namespace App\Domain\Integration\Notification\Shoutrrr;

use App\Infrastructure\Daemon\ProcessFactory;

final readonly class LiveShoutrrr implements Shoutrrr
{
    public function __construct(
        private ProcessFactory $processFactory,
    ) {
    }

    public function send(ShoutrrrUrl $shoutrrrUrl, string $message, string $title): void
    {
        $process = $this->processFactory->createSymfonyProcess(['shoutrrr', 'send', '--url', (string) $shoutrrrUrl, '--message', $message, '--title', $title]);
        $process->run();

        if ($process->isSuccessful()) {
            return;
        }

        throw new CouldNotSendShoutrrrNotification($process->getErrorOutput());
    }
}
