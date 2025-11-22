<?php

declare(strict_types=1);

namespace App\Domain\Gear\CustomGear;

final class InvalidCustomGearConfig extends \RuntimeException
{
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        $message = sprintf(
            'config/app/config.yaml gear.customGear: %s',
            $message,
        );
        parent::__construct($message, $code, $previous);
    }
}
