<?php

namespace App\Tests\Console\Webook;

use App\Console\Webook\StravaViewWebhookSubscriptionConsoleCommand;
use App\Tests\Console\ConsoleCommandTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class StravaViewWebhookSubscriptionConsoleCommandTest extends ConsoleCommandTestCase
{
    use MatchesSnapshots;

    private StravaViewWebhookSubscriptionConsoleCommand $stravaViewWebhookSubscriptionConsoleCommand;
    private MockObject $strava;

    public function testExecute(): void
    {
        $command = $this->getCommandInApplication('app:strava:webhooks-view');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $this->assertMatchesTextSnapshot(str_replace(' ', '', $commandTester->getDisplay()));
    }

    protected function getConsoleCommand(): Command
    {
        return $this->stravaViewWebhookSubscriptionConsoleCommand;
    }
}
