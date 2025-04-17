<?php

declare(strict_types=1);

namespace App\Domain\App\BuildRewindHtml;

use App\Domain\Strava\Rewind\RewindItem;
use App\Domain\Strava\Rewind\RewindRepository;
use App\Infrastructure\CQRS\Command;
use App\Infrastructure\CQRS\CommandHandler;
use League\Flysystem\FilesystemOperator;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final readonly class BuildRewindHtmlCommandHandler implements CommandHandler
{
    public function __construct(
        private RewindRepository $rewindRepository,
        private Environment $twig,
        private FilesystemOperator $buildStorage,
        private TranslatorInterface $translator,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof BuildRewindHtml);

        $now = $command->getCurrentDateTime();
        $availableRewindYears = $this->rewindRepository->getAvailableRewindYears($now);

        $render = [
            'now' => $now,
            'availableRewindYears' => $availableRewindYears,
            'activeRewindYear' => $availableRewindYears->getFirst(),
            'rewindItems' => [
                RewindItem::from(
                    icon: 'calendar',
                    title: $this->translator->trans('Daily activities'),
                    subTitle: null,
                    content: ''
                ),
                RewindItem::from(
                    icon: 'tools',
                    title: $this->translator->trans('Gear'),
                    subTitle: $this->translator->trans('Total hours spent using gear'),
                    content: ''
                ),
                RewindItem::from(
                    icon: 'ruler',
                    title: $this->translator->trans('Distance ranges'),
                    subTitle: $this->translator->trans('Number of activities within a distance range'),
                    content: ''
                ),
                RewindItem::from(
                    icon: 'trophy',
                    title: $this->translator->trans('Biggest activity'),
                    subTitle: 'TODO: Name of the activity',
                    content: ''
                ),
                RewindItem::from(
                    icon: 'medal',
                    title: $this->translator->trans('PRs'),
                    subTitle: $this->translator->trans('PRs achieved per month'),
                    content: ''
                ),
                RewindItem::from(
                    icon: 'thumbs-up',
                    title: $this->translator->trans('Socials'),
                    subTitle: $this->translator->trans('Total kudos and comments received'),
                    content: ''
                ),
                RewindItem::from(
                    icon: 'rocket',
                    title: $this->translator->trans('Distance'),
                    subTitle: $this->translator->trans('Total distance per month'),
                    content: ''
                ),
                RewindItem::from(
                    icon: 'mountain',
                    title: $this->translator->trans('Elevation'),
                    subTitle: $this->translator->trans('Total elevation per month'),
                    content: ''
                ),
                RewindItem::from(
                    icon: 'muscle',
                    title: $this->translator->trans('Activity count'),
                    subTitle: $this->translator->trans('Number of activities per month'),
                    content: ''
                ),
                RewindItem::from(
                    icon: 'watch',
                    title: $this->translator->trans('Total hours'),
                    subTitle: $this->translator->trans('Total hours spent per sport type'),
                    content: ''
                ),
                RewindItem::from(
                    icon: 'fire',
                    title: $this->translator->trans('Streaks'),
                    subTitle: $this->translator->trans('Longest streaks'),
                    content: ''
                ),
                RewindItem::from(
                    icon: 'bed',
                    title: $this->translator->trans('Rest days'),
                    subTitle: $this->translator->trans('Rest days vs. active days'),
                    content: ''
                ),
                RewindItem::from(
                    icon: 'clock',
                    title: $this->translator->trans('Start times'),
                    subTitle: $this->translator->trans('Activity start times'),
                    content: ''
                ),
                RewindItem::from(
                    icon: 'image',
                    title: $this->translator->trans('Photo'),
                    subTitle: 'TODO: date of picture',
                    content: ''
                ),
                RewindItem::from(
                    icon: 'globe',
                    title: $this->translator->trans('Activity locations'),
                    subTitle: $this->translator->trans('Locations over the globe'),
                    content: ''
                ),
            ],
        ];

        $this->buildStorage->write(
            'rewind.html',
            $this->twig->load('html/rewind/rewind.html.twig')->render($render),
        );

        foreach ($availableRewindYears as $availableRewindYear) {
            $render['activeRewindYear'] = $availableRewindYear;
            $this->buildStorage->write(
                sprintf('rewind/%s.html', $availableRewindYear),
                $this->twig->load('html/rewind/rewind.html.twig')->render($render),
            );
        }
    }
}
