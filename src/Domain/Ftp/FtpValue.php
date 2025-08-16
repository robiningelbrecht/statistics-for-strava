<?php

declare(strict_types=1);

namespace App\Domain\Ftp;

final readonly class FtpValue implements \Stringable, \JsonSerializable
{
    private function __construct(
        private int $value,
    ) {
        if ($this->value < 1) {
            throw new \InvalidArgumentException('Minimum FTP of 1 expected');
        }
    }

    public static function fromInt(int $value): self
    {
        return new self($value);
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }

    public function jsonSerialize(): string
    {
        return (string) $this;
    }
}
