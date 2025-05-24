<?php

declare(strict_types=1);

namespace App\Domain\App\Config;

final class CouldNotParseYamlConfig extends \RuntimeException
{
    public static function configFileNotFound(): self
    {
        return new self(
            'App configuration file not found. Please ensure the following:
            
1) You properly configured your volumes in your docker-compose.yml 
2) A file named "config.yaml" exists in the expected location.

Refer to the README for detailed setup instructions.
If you are upgrading from v1.x.x to v2.x.x, please consult the Wiki for additional guidance.'
        );
    }

    public static function invalidYml(string $errorMessage): self
    {
        return new self($errorMessage);
    }
}
