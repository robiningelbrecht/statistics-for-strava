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
        // 1. VOLUME - “How much do you train?”
        // weekly_hours = total_training_hours_in_period / (total_days_in_period / 7)
        // score = min(100, weekly_hours / 10 * 100)
        // 10 h/week = very active amateur (works across sports)

        // 2. CONSISTENCY - “How often do you train?”
        // consistency = active_days_in_period / total_days_in_period
        // score = min(100, consistency / 0.7 * 100)
        // 5 days/week ≈ excellent consistency

        // 3. INTENSITY - “How hard do you train?”
        // intensity = activities_with_high_effort / total_activities
        // score = min(100, intensity / 0.25 * 100)
        // 0.25 = realistic upper bound for sustainable hard training

        // 4. DURATION - “How long are your sessions?”
        // score = min(100, median_duration_minutes / 90 * 100)
        // Median > 90 min = endurance-leaning athlete

        // 5. DENSITY - “How packed is your training?”
        // density = training_hours / active_days
        // score = min(100, hours_per_active_day / 2 * 100)
        // 2h per training day = high density

        // 6. VARIETY - “How diverse is your training?”
        // dominant_sport_fraction = max(activities_per_sport_type) / total_activities
        // variety = 1 - dominant_sport_fraction
        // score = min(100, variety / 0.5 * 100)
        // 0.5 is the anchor that defines “max meaningful variety”
        return $this->twig->load('html/dashboard/widget/widget--athlete-profile.html.twig')->render([
            'athleteProfileChart' => Json::encode(
                AthleteProfileChart::create($this->translator)->build()
            ),
        ]);
    }
}
