<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity\Eddington\Config;

use App\Domain\Strava\Activity\SportType\SportTypes;

final readonly class EddingtonConfigItem
{
    private function __construct(
        private string $label,
        private bool $showInNavBar,
        private SportTypes $sportTypesToInclude,
    ) {
    }

    public static function create(
        string $label,
        bool $showInNavBar,
        SportTypes $sportTypesToInclude,
    ): self {
        return new self(
            label: $label,
            showInNavBar: $showInNavBar,
            sportTypesToInclude: $sportTypesToInclude
        );
    }

    public function getId(): string
    {
        /** @var string $sanitizedLabel */
        $sanitizedLabel = preg_replace('/-+/', '-', str_replace(' ', '-',
            preg_replace('/[^a-z0-9 ]/', '', strtolower($this->label)) // @phpstan-ignore argument.type
        ));

        return $sanitizedLabel;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function showInNavBar(): bool
    {
        return $this->showInNavBar;
    }

    public function getSportTypesToInclude(): SportTypes
    {
        return $this->sportTypesToInclude;
    }
}
