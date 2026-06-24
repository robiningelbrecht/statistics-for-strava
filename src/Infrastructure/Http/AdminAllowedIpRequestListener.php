<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Infrastructure\Config\AdminAllowedIpAddresses;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class AdminAllowedIpRequestListener implements EventSubscriberInterface
{
    public function __construct(
        private AdminAllowedIpAddresses $allowedIps,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if ($this->allowedIps->isEmpty()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();
        if ('/admin' !== $path && !str_starts_with($path, '/admin/')) {
            return;
        }

        $clientIp = $request->headers->get('CF-Connecting-IP') ?? $request->getClientIp();
        if ($this->allowedIps->contains($clientIp)) {
            return;
        }

        throw new NotFoundHttpException();
    }

    /**
     * @codeCoverageIgnore
     */
    public static function getSubscribedEvents(): array
    {
        // Run before the firewall (priority 8) so disallowed IPs never reach the login form.
        return [KernelEvents::REQUEST => [['onKernelRequest', 16]]];
    }
}
