<?php

declare(strict_types=1);

namespace App\Console;

use App\Domain\Strava\Webhook\WebhookConfig;
use App\Domain\Strava\Webhook\WebhookSubscriptionException;
use App\Domain\Strava\Webhook\WebhookSubscriptionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:strava:webhook:subscribe',
    description: 'Subscribe to Strava webhook events'
)]
final class StravaWebhookSubscribeConsoleCommand extends Command
{
    public function __construct(
        private readonly WebhookConfig $webhookConfig,
        private readonly WebhookSubscriptionService $webhookSubscriptionService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->webhookConfig->isEnabled()) {
            $io->error('Webhooks are not enabled in config.yaml');
            $io->note('Set webhooks.enabled to true in config/app/config.yaml to enable webhooks.');

            return Command::FAILURE;
        }

        if (!$this->webhookConfig->isConfigured()) {
            $io->error('Webhooks are not properly configured in config.yaml');
            $io->note([
                'Make sure the following are set in config/app/config.yaml:',
                '  - webhooks.enabled: true',
                '  - webhooks.callbackUrl: your public webhook URL',
                '  - webhooks.verifyToken: a secret token of your choice',
            ]);

            return Command::FAILURE;
        }

        $io->section('Creating Strava Webhook Subscription');

        $io->writeln([
            'Callback URL: '.$this->webhookConfig->getCallbackUrl(),
            'Verify Token: '.$this->webhookConfig->getVerifyToken(),
        ]);

        try {
            $result = $this->webhookSubscriptionService->createSubscription(
                $this->webhookConfig->getCallbackUrl(),
                $this->webhookConfig->getVerifyToken()
            );

            $io->success('Webhook subscription created successfully!');
            $io->writeln('Subscription ID: '.$result['id']);

            $io->note([
                'Strava will now send webhook events to your callback URL when:',
                '  - A new activity is created',
                '  - An activity is updated',
                '  - An activity is deleted',
                '  - An athlete revokes access',
                '',
                'The app will automatically import and build when receiving activity events.',
            ]);

            return Command::SUCCESS;
        } catch (WebhookSubscriptionException $e) {
            $io->error('Failed to create webhook subscription: '.$e->getMessage());

            $io->note([
                'Common issues:',
                '  - Callback URL must be publicly accessible',
                '  - Callback URL must respond to validation within 2 seconds',
                '  - You may already have a subscription (check with: app:strava:webhook:view)',
                '  - Your Strava API credentials may be incorrect',
            ]);

            return Command::FAILURE;
        }
    }
}
