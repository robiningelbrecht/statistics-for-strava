<?php

declare(strict_types=1);

namespace App\Domain\Athlete;

use App\Domain\Athlete\MaxHeartRate\MaxHeartRateFormula;
use App\Domain\Athlete\RestingHeartRate\RestingHeartRateFormula;
use App\Domain\Integration\AI\SupportsAITooling;
use App\Infrastructure\ValueObject\String\Name;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final class Athlete implements \JsonSerializable, SupportsAITooling
{
    private ?MaxHeartRateFormula $maxHeartRateFormula = null;
    private ?RestingHeartRateFormula $restingHeartRateFormula = null;

    private function __construct(
        /** @var array<string, mixed> */
        private readonly array $data,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function create(
        array $data,
    ): self {
        return new self(
            data: $data,
        );
    }

    public function setMaxHeartRateFormula(MaxHeartRateFormula $maxHeartRateFormula): self
    {
        $this->maxHeartRateFormula = $maxHeartRateFormula;

        return $this;
    }

    public function setRestingHeartRateFormula(RestingHeartRateFormula $restingHeartRateFormula): self
    {
        $this->restingHeartRateFormula = $restingHeartRateFormula;

        return $this;
    }

    public function getAthleteId(): string
    {
        return (string) $this->data['id'];
    }

    public function getBirthDate(): SerializableDateTime
    {
        return SerializableDateTime::fromString($this->data['birthDate']);
    }

    public function getAgeInYears(SerializableDateTime $on): int
    {
        return $this->getBirthDate()->diff($on)->y;
    }

    public function getRestingHeartRateFormula(SerializableDateTime $on): int
    {
        if (is_null($this->restingHeartRateFormula)) {
            throw new \RuntimeException('Resting heart rate formula not set');
        }

        return $this->restingHeartRateFormula->calculate(
            age: $this->getAgeInYears($on),
            on: $on
        );
    }

    public function getMaxHeartRate(SerializableDateTime $on): int
    {
        if (is_null($this->maxHeartRateFormula)) {
            throw new \RuntimeException('Max heart rate formula not set');
        }

        return $this->maxHeartRateFormula->calculate(
            age: $this->getAgeInYears($on),
            on: $on
        );
    }

    public function getName(): Name
    {
        return Name::fromString(sprintf('%s %s', $this->data['firstname'] ?? 'John', $this->data['lastname'] ?? 'Doe'));
    }

    public function getFirstLetterOfFirstName(): string{
        return substr((string) $this->data['firstname'] ?? 'J', 0, 1);
    }

    public function getSex(): string
    {
        return strtoupper($this->data['sex'] ?? 'M');
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->exportForAITooling();
    }

    /**
     * @return array<string, mixed>
     */
    public function exportForAITooling(): array
    {
        return $this->data;
    }
}
