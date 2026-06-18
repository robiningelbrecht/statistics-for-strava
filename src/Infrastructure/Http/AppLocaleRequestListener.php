<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Infrastructure\Localisation\Locale;
use Carbon\Carbon;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Translation\LocaleSwitcher;

final readonly class AppLocaleRequestListener implements EventSubscriberInterface
{
    public function __construct(
        private Locale $locale,
        private LocaleSwitcher $localeSwitcher,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $event->getRequest()->setLocale($this->locale->value);
        $this->localeSwitcher->setLocale($this->locale->value);
        Carbon::setLocale($this->locale->value);
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => [['onKernelRequest', 14]]];
    }
}
