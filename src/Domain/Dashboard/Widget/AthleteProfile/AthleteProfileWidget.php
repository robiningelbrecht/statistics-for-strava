<?php

namespace App\Domain\Dashboard\Widget\AthleteProfile;

use App\Domain\Dashboard\Widget\Widget;
use App\Domain\Dashboard\Widget\WidgetConfiguration;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final readonly class AthleteProfileWidget implements Widget
{
    public function __construct(
        private Environment $twig,
        private TranslatorInterface $translator,
    ) {
    }

    public function getDefaultConfiguration(): WidgetConfiguration
    {
        return WidgetConfiguration::empty();
    }

    public function guardValidConfiguration(WidgetConfiguration $configuration): void
    {
    }

    public function render(SerializableDateTime $now, WidgetConfiguration $configuration): string
    {
        return $this->twig->load('html/dashboard/widget/widget--athlete-profile.html.twig')->render([
            'athleteProfileChart' => Json::encode(
                AthleteProfileChart::create($this->translator)->build()
            ),
        ]);
    }
}
