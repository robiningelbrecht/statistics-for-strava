<?php

declare(strict_types=1);

namespace App\Console;

use App\Domain\Strava\Webhook\WebhookSubscriptionException;
use App\Domain\Strava\Webhook\WebhookSubscriptionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:strava:webhook:unsubscribe',
    description: 'Delete a Strava webhook subscription'
)]
final class StravaWebhookUnsubscribeConsoleCommand extends Command
{
    public function __construct(
        private readonly WebhookSubscriptionService $webhookSubscriptionService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'subscription-id',
            InputArgument::REQUIRED,
            'The webhook subscription ID to delete'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var string $subscriptionIdStr */
        $subscriptionIdStr = $input->getArgument('subscription-id');
        $subscriptionId = (int) $subscriptionIdStr;

        $io->section('Deleting Strava Webhook Subscription');

        if (!$io->confirm('Are you sure you want to delete subscription ID ' . $subscriptionId . '?', false)) {
            $io->info('Aborted');
            return Command::SUCCESS;
        }

        try {
            $this->webhookSubscriptionService->deleteSubscription($subscriptionId);

            $io->success('Webhook subscription deleted successfully!');
            $io->note('You will no longer receive automatic updates from Strava.');

            return Command::SUCCESS;
        } catch (WebhookSubscriptionException $e) {
            $io->error('Failed to delete webhook subscription: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

