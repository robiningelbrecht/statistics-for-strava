<?php

declare(strict_types=1);

namespace App\Domain\Calendar;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class Week implements \JsonSerializable
{
    private function __construct(
        private int $year,
        private int $weekNumber,
    ) {
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
        return SerializableDateTime::fromYearAndWeekNumber($this->year, $this->weekNumber)->translatedFormat('M Y');
    }

    public function getLabelFromTo(): string
    {
        return implode(' - ', [
            $this->getFrom()->translatedFormat('d M'),
            $this->getTo()->translatedFormat('d M'),
        ]);
    }

    public function getFrom(): SerializableDateTime
    {
        return SerializableDateTime::fromYearAndWeekNumber($this->year, $this->weekNumber);
    }

    public function getTo(): SerializableDateTime
    {
        return SerializableDateTime::fromYearAndWeekNumber($this->year, $this->weekNumber, 7);
    }

    /**
     * @return array{from: string, to: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'from' => $this->getFrom()->format('Y-m-d'),
            'to' => $this->getTo()->format('Y-m-d'),
        ];
    }
}
