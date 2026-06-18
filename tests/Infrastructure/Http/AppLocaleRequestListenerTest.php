<?php

namespace App\Tests\Infrastructure\Http;

use App\Infrastructure\Http\AppLocaleRequestListener;
use App\Infrastructure\Localisation\Locale;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Translation\LocaleSwitcher;

class AppLocaleRequestListenerTest extends TestCase
{
    private string $originalLocale;

    public function testItShouldApplyTheConfiguredLocaleForMainRequests(): void
    {
        $localeSwitcher = $this->createMock(LocaleSwitcher::class);
        $localeSwitcher
            ->expects($this->once())
            ->method('setLocale')
            ->with('nl_BE');

        $listener = new AppLocaleRequestListener(Locale::nl_BE, $localeSwitcher);

        $request = Request::create('/admin');

        $listener->onKernelRequest(new RequestEvent(
            kernel: $this->createStub(HttpKernelInterface::class),
            request: $request,
            requestType: HttpKernelInterface::MAIN_REQUEST,
        ));

        $this->assertSame('nl_BE', $request->getLocale());
        $this->assertSame('nl_BE', Carbon::getLocale());
    }

    public function testItShouldDoNothingForSubRequests(): void
    {
        $localeSwitcher = $this->createMock(LocaleSwitcher::class);
        $localeSwitcher
            ->expects($this->never())
            ->method('setLocale');

        $listener = new AppLocaleRequestListener(Locale::nl_BE, $localeSwitcher);

        $listener->onKernelRequest(new RequestEvent(
            kernel: $this->createStub(HttpKernelInterface::class),
            request: Request::create('/admin'),
            requestType: HttpKernelInterface::SUB_REQUEST,
        ));
    }

    protected function setUp(): void
    {
        $this->originalLocale = Carbon::getLocale();
    }

    protected function tearDown(): void
    {
        Carbon::setLocale($this->originalLocale);
    }
}
