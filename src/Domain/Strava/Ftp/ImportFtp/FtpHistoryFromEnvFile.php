<?php

declare(strict_types=1);

namespace App\Domain\Strava\Ftp\ImportFtp;

use App\Domain\Strava\Ftp\Ftp;
use App\Domain\Strava\Ftp\Ftps;
use App\Domain\Strava\Ftp\FtpValue;
use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final readonly class FtpHistoryFromEnvFile
{
    private Ftps $ftps;

    /**
     * @param array<string, int> $ftps
     */
    private function __construct(
        array $ftps,
    ) {
        $this->ftps = Ftps::empty();

        foreach ($ftps as $setOn => $ftpValue) {
            try {
                $this->ftps->add(Ftp::fromState(
                    setOn: SerializableDateTime::fromString($setOn),
                    ftp: FtpValue::fromInt($ftpValue)
                ));
            } catch (\DateMalformedStringException) {
                throw new \InvalidArgumentException(sprintf('Invalid date "%s" set in FTP_HISTORY in .env file', $setOn));
            }
        }
    }

    public function getAll(): Ftps
    {
        return $this->ftps;
    }

    public static function fromString(string $values): self
    {
        try {
            return new self(
                Json::decode($values)
            );
        } catch (\JsonException) {
            throw new \InvalidArgumentException('Invalid FTP_HISTORY detected in .env file. Make sure the string is valid JSON');
        }
    }
}
