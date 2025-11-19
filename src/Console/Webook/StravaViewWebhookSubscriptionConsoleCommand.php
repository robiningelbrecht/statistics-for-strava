<?php

declare(strict_types=1);

namespace App\Console\Webook;

use App\Domain\Strava\Strava;
use App\Domain\Strava\Webhook\WebhookConfig;
use App\Infrastructure\Logging\LoggableConsoleOutput;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[WithMonologChannel('console-output')]
#[AsCommand(name: 'app:strava:webhooks-view', description: 'View current Strava webhook subscription(s)')]
final class StravaViewWebhookSubscriptionConsoleCommand extends Command
{
    public function __construct(
        private readonly Strava $strava,
        private readonly WebhookConfig $webhookConfig,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output = new SymfonyStyle($input, new LoggableConsoleOutput($output, $this->logger));

        if (!$this->webhookConfig->isEnabled()) {
            $output->warning('Webhooks not enabled. Enable them in your config by setting import.webhooks.enabled = true');

            return Command::SUCCESS;
        }

        if (!$subscriptions = $this->strava->getWebhookSubscription()) {
            $output->note('No webhook subscriptions found');
            $output->note('Create a subscription with: docker compose exec app bin/console app:strava:webhooks-subscribe');

            return Command::SUCCESS;
        }

        foreach ($subscriptions as $subscription) {
            $output->success('Webhook Subscription Found');
            $output->table(
                ['Property', 'Value'],
                [
                    ['ID', $subscription['id']],
                    ['Application ID', $subscription['application_id']],
                    ['Callback URL', $subscription['callback_url']],
                    ['Created At', $subscription['created_at']],
                    ['Updated At', $subscription['updated_at']],
                ]
            );
        }

        return Command::SUCCESS;
    }
}
