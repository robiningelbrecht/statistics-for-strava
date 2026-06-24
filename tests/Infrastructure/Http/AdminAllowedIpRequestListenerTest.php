<?php

namespace App\Tests\Infrastructure\Http;

use App\Infrastructure\Config\AdminAllowedIpAddresses;
use App\Infrastructure\Http\AdminAllowedIpRequestListener;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class AdminAllowedIpRequestListenerTest extends TestCase
{
    public function testItDeniesAdminAccessFromADisallowedIp(): void
    {
        $this->expectExceptionObject(new NotFoundHttpException('Not found'));

        $this->dispatch(
            new AdminAllowedIpRequestListener(AdminAllowedIpAddresses::fromString('192.168.1.1')),
            $this->adminRequestFromIp('10.0.0.1'),
        );
    }

    public function testItAllowsAdminAccessFromAnAllowedIp(): void
    {
        $this->dispatch(
            new AdminAllowedIpRequestListener(AdminAllowedIpAddresses::fromString('192.168.1.1')),
            $this->adminRequestFromIp('192.168.1.1'),
        );

        $this->expectNotToPerformAssertions();
    }

    public function testItPrefersTheCloudflareConnectingIpHeader(): void
    {
        $request = $this->adminRequestFromIp('192.168.1.1');
        $request->headers->set('CF-Connecting-IP', '10.0.0.1');

        $this->expectExceptionObject(new NotFoundHttpException('Not found'));

        $this->dispatch(
            new AdminAllowedIpRequestListener(AdminAllowedIpAddresses::fromString('192.168.1.1')),
            $request,
        );
    }

    public function testItAllowsEveryoneWhenNoAllowListIsConfigured(): void
    {
        $this->dispatch(
            new AdminAllowedIpRequestListener(AdminAllowedIpAddresses::fromString('')),
            $this->adminRequestFromIp('10.0.0.1'),
        );

        $this->expectNotToPerformAssertions();
    }

    #[DataProvider('provideNonAdminPaths')]
    public function testItIgnoresNonAdminPaths(string $path): void
    {
        $request = Request::create($path);
        $request->server->set('REMOTE_ADDR', '10.0.0.1');

        $this->dispatch(
            new AdminAllowedIpRequestListener(AdminAllowedIpAddresses::fromString('192.168.1.1')),
            $request,
        );

        $this->expectNotToPerformAssertions();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideNonAdminPaths(): iterable
    {
        yield 'home' => ['/'];
        yield 'a path that merely starts with admin' => ['/administration'];
    }

    public function testItDoesNothingForSubRequests(): void
    {
        $listener = new AdminAllowedIpRequestListener(AdminAllowedIpAddresses::fromString('192.168.1.1'));

        $listener->onKernelRequest(new RequestEvent(
            kernel: $this->createStub(HttpKernelInterface::class),
            request: $this->adminRequestFromIp('10.0.0.1'),
            requestType: HttpKernelInterface::SUB_REQUEST,
        ));

        $this->expectNotToPerformAssertions();
    }

    private function dispatch(AdminAllowedIpRequestListener $listener, Request $request): void
    {
        $listener->onKernelRequest(new RequestEvent(
            kernel: $this->createStub(HttpKernelInterface::class),
            request: $request,
            requestType: HttpKernelInterface::MAIN_REQUEST,
        ));
    }

    private function adminRequestFromIp(string $ipAddress): Request
    {
        $request = Request::create('/admin/login');
        $request->server->set('REMOTE_ADDR', $ipAddress);

        return $request;
    }
}
