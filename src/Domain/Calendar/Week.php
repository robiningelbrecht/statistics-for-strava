<?php

declare(strict_types=1);

namespace App\Domain\Calendar;

use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class Week implements \JsonSerializable
{
    private SerializableDateTime $date;

    private function __construct(
        SerializableDateTime $date,
    ) {
        $this->date = SerializableDateTime::fromString($date->modify('monday this week')->format('Y-m-d'));
    }

    public static function fromDate(
        SerializableDateTime $date,
    ): self {
        return new self($date);
    }

    public function getId(): string
    {
        return sprintf('%s-%s-%s', $this->date->format('Y'), $this->date->format('m'), $this->date->format('d'));
    }

    public function getLabel(): string
    {
        return $this->date->translatedFormat('M Y');
    }

    public function getFrom(): SerializableDateTime
    {
        return $this->date;
    }

    public function getTo(): SerializableDateTime
    {
        return SerializableDateTime::fromString($this->date->modify('sunday this week')->format('Y-m-d'));
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
