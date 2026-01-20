<?php

namespace App\Tests\Domain\Integration\Notification\Shoutrrr;

use App\Domain\Integration\Notification\Shoutrrr\CouldNotSendShoutrrrNotification;
use App\Domain\Integration\Notification\Shoutrrr\LiveShoutrrr;
use App\Domain\Integration\Notification\Shoutrrr\Shoutrrr;
use App\Domain\Integration\Notification\Shoutrrr\ShoutrrrUrl;
use App\Infrastructure\Daemon\ProcessFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class LiveShoutrrrTest extends TestCase
{
    private Shoutrrr $shoutrrr;
    private MockObject $processFactory;

    public function testSend(): void
    {
        $process = $this->createStub(Process::class);
        $process->method('run');
        $process->method('isSuccessful')->willReturn(true);

        $this->processFactory
            ->expects(self::once())
            ->method('create')
            ->with(
                ['shoutrrr', 'send', '--url', 'https://api.live.shoutrr.com', '--message', 'message', '--title', 'title']
            )
            ->willReturn($process);

        $this->shoutrrr->send(ShoutrrrUrl::fromString('https://api.live.shoutrr.com'), 'message', 'title');
    }

    public function testSendWhenFailure(): void
    {
        $process = $this->createStub(Process::class);
        $process->method('run');
        $process->method('isSuccessful')->willReturn(false);
        $process->method('getErrorOutput')->willReturn('Error!');

        $this->processFactory
            ->expects(self::once())
            ->method('create')
            ->with(
                ['shoutrrr', 'send', '--url', 'https://api.live.shoutrr.com', '--message', 'message', '--title', 'title']
            )
            ->willReturn($process);

        $this->expectExceptionObject(new CouldNotSendShoutrrrNotification('Error!'));
        $this->shoutrrr->send(ShoutrrrUrl::fromString('https://api.live.shoutrr.com'), 'message', 'title');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->shoutrrr = new LiveShoutrrr(
            $this->processFactory = $this->createMock(ProcessFactory::class)
        );
    }
}
