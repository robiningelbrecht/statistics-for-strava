<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueObject\String;

final readonly class AllowedIpAddresses
{
    /**
     * @param string[] $ipAddresses
     */
    private function __construct(
        private array $ipAddresses,
    ) {
    }

    public static function fromString(string $string): self
    {
        $ipAddresses = array_values(array_filter(array_map(
            trim(...),
            explode(',', $string)
        )));

        foreach ($ipAddresses as $ipAddress) {
            if (false === filter_var($ipAddress, FILTER_VALIDATE_IP)) {
                throw new \InvalidArgumentException(sprintf('"%s" is not a valid IP address', $ipAddress));
            }
        }

        return new self($ipAddresses);
    }

    public function contains(?string $ipAddress): bool
    {
        return null !== $ipAddress && in_array($ipAddress, $this->ipAddresses, true);
    }

    public function isEmpty(): bool
    {
        return [] === $this->ipAddresses;
    }
}
