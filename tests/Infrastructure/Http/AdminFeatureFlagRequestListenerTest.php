<?php

namespace App\Tests\Infrastructure\Http;

use App\Infrastructure\Http\AdminFeatureFlagRequestListener;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class AdminFeatureFlagRequestListenerTest extends TestCase
{
    private const string ADMIN_ENV_VAR = 'FEATURE_ENABLE_ADMIN';

    private bool $envVarWasSet;
    private mixed $originalEnvVarValue = null;

    private AdminFeatureFlagRequestListener $listener;

    public function testItShouldDoNothingForSubRequests(): void
    {
        $_SERVER[self::ADMIN_ENV_VAR] = 'false';

        $event = $this->createEvent('/admin', HttpKernelInterface::SUB_REQUEST);

        $this->listener->onKernelRequest($event);

        $this->expectNotToPerformAssertions();
    }

    public function testItShouldDoNothingWhenTheFeatureFlagIsEnabled(): void
    {
        $_SERVER[self::ADMIN_ENV_VAR] = 'true';

        $event = $this->createEvent('/admin/upload');

        $this->listener->onKernelRequest($event);

        $this->expectNotToPerformAssertions();
    }

    public function testItShouldDoNothingForNonAdminPathsWhenDisabled(): void
    {
        $_SERVER[self::ADMIN_ENV_VAR] = 'false';

        $event = $this->createEvent('/dashboard');

        $this->listener->onKernelRequest($event);

        $this->expectNotToPerformAssertions();
    }

    #[DataProvider('provideAdminPaths')]
    public function testItShouldBlockAdminPathsWhenDisabled(string $path): void
    {
        $_SERVER[self::ADMIN_ENV_VAR] = 'false';

        $event = $this->createEvent($path);

        $this->expectException(NotFoundHttpException::class);

        $this->listener->onKernelRequest($event);
    }

    public static function provideAdminPaths(): iterable
    {
        yield '/admin' => ['/admin'];
        yield '/admin/upload' => ['/admin/upload'];
        yield '/admin/login' => ['/admin/login'];
    }

    private function createEvent(string $path, int $requestType = HttpKernelInterface::MAIN_REQUEST): RequestEvent
    {
        return new RequestEvent(
            $this->createStub(HttpKernelInterface::class),
            Request::create($path),
            $requestType,
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new AdminFeatureFlagRequestListener();

        $this->envVarWasSet = array_key_exists(self::ADMIN_ENV_VAR, $_SERVER);
        $this->originalEnvVarValue = $_SERVER[self::ADMIN_ENV_VAR] ?? null;
    }

    protected function tearDown(): void
    {
        if ($this->envVarWasSet) {
            $_SERVER[self::ADMIN_ENV_VAR] = $this->originalEnvVarValue;

            return;
        }

        unset($_SERVER[self::ADMIN_ENV_VAR]);
    }
}
