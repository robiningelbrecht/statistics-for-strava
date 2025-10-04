<?php

declare(strict_types=1);

namespace App\Domain\Activity\Eddington\Config;

use App\Domain\Activity\SportType\SportTypes;
use App\Infrastructure\ValueObject\String\Name;

final readonly class EddingtonConfigItem
{
    private function __construct(
        private string $label,
        private bool $showInNavBar,
        private SportTypes $sportTypesToInclude,
        private bool $showInDashboardWidget,
    ) {
    }

    public static function create(
        string $label,
        bool $showInNavBar,
        SportTypes $sportTypesToInclude,
        bool $showInDashboardWidget,
    ): self {
        return new self(
            label: $label,
            showInNavBar: $showInNavBar,
            sportTypesToInclude: $sportTypesToInclude,
            showInDashboardWidget: $showInDashboardWidget,
        );
    }

    public function getId(): string
    {
        return Name::fromString($this->label)->kebabCase();
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

    public function showInDashboardWidget(): bool
    {
        return $this->showInDashboardWidget;
    }
}
