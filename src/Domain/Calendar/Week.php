<?php

declare(strict_types=1);

namespace App\Domain\Calendar;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class Week
{
    private SerializableDateTime $firstDay;

    private function __construct(
        private int $year,
        private int $weekNumber,
    ) {
        $this->firstDay = SerializableDateTime::fromYearAndWeekNumber($this->year, $this->weekNumber);
    }

    public static function fromYearAndWeekNumber(
        int $year,
        int $weekNumber,
    ): self {
        return new self(
            year: $year,
            weekNumber: $weekNumber,
        );
    }

    public function getId(): string
    {
        return $this->year.'-'.$this->weekNumber;
    }

    public function getLabel(): string
    {
        return $this->firstDay->translatedFormat('M Y');
    }
}
