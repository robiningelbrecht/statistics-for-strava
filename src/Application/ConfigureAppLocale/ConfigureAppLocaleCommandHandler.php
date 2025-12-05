<?php

declare(strict_types=1);

namespace App\Application\ConfigureAppLocale;

use App\Infrastructure\CQRS\Command\Command;
use App\Infrastructure\CQRS\Command\CommandHandler;
use App\Infrastructure\Localisation\Locale;
use Carbon\Carbon;
use Symfony\Component\Translation\LocaleSwitcher;

final readonly class ConfigureAppLocaleCommandHandler implements CommandHandler
{
    public function __construct(
        private LocaleSwitcher $localeSwitcher,
        private Locale $locale,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof ConfigureAppLocale);

        $this->localeSwitcher->setLocale($this->locale->value);
        Carbon::setLocale($this->locale->value);
    }
}
