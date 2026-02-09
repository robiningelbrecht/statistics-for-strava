<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueObject\String;

final readonly class CompressedString implements \Stringable
{
    private const int DEFAULT_ZSTD_LEVEL = 3;

    private function __construct(
        private string $compressedValue,
    ) {
    }

    public static function fromUncompressed(string $value): self
    {
        $compressed = @zstd_compress($value, self::DEFAULT_ZSTD_LEVEL);
        if (false === $compressed) {
            throw new \RuntimeException('ZSTD compression failed'); // @codeCoverageIgnore
        }

        return new self($compressed);
    }

    public static function fromCompressed(string $value): self
    {
        return new self($value);
    }

    public function uncompress(): string
    {
        $uncompressed = @zstd_uncompress($this->compressedValue);
        if (false === $uncompressed) {
            throw new \RuntimeException('ZSTD decompression failed');
        }

        return $uncompressed;
    }

    public function __toString(): string
    {
        return $this->compressedValue;
    }
}
