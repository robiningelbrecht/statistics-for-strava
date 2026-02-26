<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Infrastructure\ValueObject\Identifier\UuidFactory;
use Symfony\Component\Asset\VersionStrategy\VersionStrategyInterface;

final readonly class UuidAssetVersioningStrategy implements VersionStrategyInterface
{
    private string $format;
    private string $version;

    public function __construct(
        private UuidFactory $uuidFactory,
    ) {
        $this->format = '%s?%s';
        $this->version = $this->uuidFactory->random();
    }

    public function getVersion(string $path): string
    {
        return $this->version;
    }

    public function applyVersion(string $path): string
    {
        $versionized = \sprintf($this->format, ltrim($path, '/'), $this->getVersion($path));

        if ($path && '/' === $path[0]) {
            return '/'.$versionized;
        }

        return $versionized;
    }
}
