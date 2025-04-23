<?php

declare(strict_types=1);

namespace App\Infrastructure\CQRS\Command\Bus;

class CanNotRegisterCommandHandler extends \RuntimeException
{
}
