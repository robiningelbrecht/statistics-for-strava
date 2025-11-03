<?php

declare(strict_types=1);

namespace App\Console;

use App\Domain\Strava\Webhook\WebhookSubscriptionException;
use App\Domain\Strava\Webhook\WebhookSubscriptionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:strava:webhook:view',
    description: 'View current Strava webhook subscription'
)]
final class StravaWebhookViewConsoleCommand extends Command
{
    public function __construct(
        private readonly WebhookSubscriptionService $webhookSubscriptionService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->section('Viewing Strava Webhook Subscription');

        try {
            $subscriptions = $this->webhookSubscriptionService->viewSubscription();

            if (empty($subscriptions)) {
                $io->warning('No webhook subscriptions found');
                $io->note('Create a subscription with: bin/console app:strava:webhook:subscribe');

                return Command::SUCCESS;
            }

            foreach ($subscriptions as $subscription) {
                $io->success('Webhook Subscription Found');
                $io->table(
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
        } catch (WebhookSubscriptionException $e) {
            $io->error('Failed to view webhook subscription: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
