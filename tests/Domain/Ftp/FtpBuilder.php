<?php

declare(strict_types=1);

namespace App\Tests\Domain\Ftp;

use App\Domain\Ftp\Ftp;
use App\Domain\Ftp\FtpValue;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;

final class FtpBuilder
{
    private SerializableDateTime $setOn;
    private FtpValue $ftp;

    private function __construct()
    {
        $this->setOn = SerializableDateTime::fromString('2023-01-04');
        $this->ftp = FtpValue::fromInt(200);
    }

    public static function fromDefaults(): self
    {
        return new self();
    }

    public function build(): Ftp
    {
        return Ftp::fromState(
            setOn: $this->setOn,
            ftp: $this->ftp
        );
    }

    public function withSetOn(SerializableDateTime $setOn): self
    {
        $this->setOn = $setOn;

        return $this;
    }

    public function withFtp(FtpValue $ftp): self
    {
        $this->ftp = $ftp;

        return $this;
    }
}
