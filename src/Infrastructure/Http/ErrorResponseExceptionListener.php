<?php

namespace App\Infrastructure\Http;

use App\Infrastructure\Config\PlatformEnvironment;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class ErrorResponseExceptionListener implements EventSubscriberInterface
{
    public function __construct(
        private PlatformEnvironment $platformEnvironment,
    ) {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $statusCode = match (true) {
            $exception instanceof NotFoundHttpException => HttpStatusCode::NOT_FOUND,
            $exception instanceof \InvalidArgumentException ,
            $exception instanceof BadRequestException => HttpStatusCode::BAD_REQUEST,
            $exception instanceof TooManyRequestsHttpException => HttpStatusCode::TOO_MANY_REQUESTS,
            default => HttpStatusCode::INTERNAL_SERVER_ERROR,
        };

        $event->allowCustomResponseCode();
        $response = JsonErrorResponse::fromThrowableAndEnvironment($exception, $statusCode, $this->platformEnvironment);
        $event->setResponse($response);
    }

    /**
     * @codeCoverageIgnore
     */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => 'onKernelException'];
    }
}
