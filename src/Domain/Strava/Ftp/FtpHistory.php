<?php

declare(strict_types=1);

namespace App\Domain\Strava\Ftp;

use App\Domain\Integration\AI\SupportsAITooling;
use App\Infrastructure\Exception\EntityNotFound;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final class FtpHistory implements SupportsAITooling
{
    /** @var Ftp[] */
    private array $ftps;

    /**
     * @param array<string, int> $ftps
     */
    private function __construct(
        array $ftps,
    ) {
        $this->ftps = [];

        foreach ($ftps as $setOn => $ftpValue) {
            try {
                $setOnDate = SerializableDateTime::fromString($setOn);
                $this->ftps[$setOnDate->getTimestamp()] = Ftp::fromState(
                    setOn: $setOnDate,
                    ftp: FtpValue::fromInt($ftpValue)
                );
            } catch (\DateMalformedStringException) {
                throw new \InvalidArgumentException(sprintf('Invalid date "%s" set for athlete ftpHistory in config.yaml file', $setOn));
            }
        }

        krsort($this->ftps);
    }

    public function findAll(): Ftps
    {
        $ftps = $this->ftps;
        // We want to sort by date in ascending order
        ksort($ftps);

        return Ftps::fromArray($ftps);
    }

    public function find(SerializableDateTime $on): Ftp
    {
        $on = SerializableDateTime::fromString($on->format('Y-m-d'));
        /** @var Ftp $ftp */
        foreach ($this->ftps as $ftp) {
            if ($on->isAfterOrOn($ftp->getSetOn())) {
                return $ftp;
            }
        }

        throw new EntityNotFound(sprintf('Ftp for date "%s" not found', $on));
    }

    /**
     * @return array<int, mixed>
     */
    public function exportForAITooling(): array
    {
        $history = [];
        foreach ($this->findAll() as $ftp) {
            $history[] = [
                'setOn' => $ftp->getSetOn()->format('Y-m-d'),
                'ftpValue' => $ftp->getFtp()->getValue(),
            ];
        }

        return $history;
    }

    /**
     * @param array<string, int> $values
     */
    public static function fromArray(array $values): self
    {
        return new self($values);
    }
}
