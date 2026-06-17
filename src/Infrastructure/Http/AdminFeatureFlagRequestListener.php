<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Infrastructure\Config\FeatureFlag;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class AdminFeatureFlagRequestListener implements EventSubscriberInterface
{
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (FeatureFlag::ADMIN->isEnabled()) {
            return;
        }

        $path = $event->getRequest()->getPathInfo();
        if ('/admin' !== $path && !str_starts_with($path, '/admin/')) {
            return;
        }

        throw new NotFoundHttpException();
    }

    /**
     * @codeCoverageIgnore
     */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => [['onKernelRequest', 16]]];
    }
}
