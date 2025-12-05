<?php

namespace App\Tests\Infrastructure\Exception;

use App\Infrastructure\Exception\ErrorResponseExceptionListener;
use App\Infrastructure\Http\HttpStatusCode;
use App\Infrastructure\ValueObject\String\PlatformEnvironment;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ErrorResponseExceptionListenerTest extends TestCase
{
    use MatchesSnapshots;

    private ErrorResponseExceptionListener $errorResponseExceptionListener;

    public function testOnKernelExceptionWithInvalidArgumentException(): void
    {
        $event = new ExceptionEvent(
            $this->createStub(HttpKernelInterface::class),
            new Request(),
            0,
            new \InvalidArgumentException()
        );

        $this->errorResponseExceptionListener->onKernelException($event);
        static::assertTrue($event->isAllowingCustomResponseCode());
        static::assertEquals(HttpStatusCode::BAD_REQUEST->value, $event->getResponse()->getStatusCode());
        $this->assertMatchesJsonSnapshot($event->getResponse()->getContent());
    }

    public function testOnKernelExceptionWithAnyException(): void
    {
        $event = new ExceptionEvent(
            $this->createStub(HttpKernelInterface::class),
            new Request(),
            0,
            new \RuntimeException('A message')
        );

        $this->errorResponseExceptionListener->onKernelException($event);
        static::assertTrue($event->isAllowingCustomResponseCode());
        static::assertEquals(HttpStatusCode::INTERNAL_SERVER_ERROR->value, $event->getResponse()->getStatusCode());
        $this->assertMatchesJsonSnapshot($event->getResponse()->getContent());
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->errorResponseExceptionListener = new ErrorResponseExceptionListener(PlatformEnvironment::PROD);
    }
}
