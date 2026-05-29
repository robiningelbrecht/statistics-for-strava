<?php

namespace App\Tests\Controller;

use App\Controller\SecuredImageRequestHandler;
use App\Infrastructure\ValueObject\String\AllowedIpAddresses;
use App\Tests\ContainerTestCase;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SecuredImageRequestHandlerTest extends ContainerTestCase
{
    // 1x1 transparent PNG.
    private const string PNG_BYTES = "\x89PNG\r\n\x1a\n\x00\x00\x00\rIHDR\x00\x00\x00\x01\x00\x00\x00\x01\x08\x06\x00\x00\x00\x1f\x15\xc4\x89\x00\x00\x00\x0bIDATx\xda\x63\xf8\xcf\xc0\xf0\x1f\x00\x05\x05\x02\x00\x4a\xd0\x9d\x6b\x00\x00\x00\x00IEND\xaeB`\x82";

    private SecuredImageRequestHandler $securedImageRequestHandler;
    private FilesystemOperator $fileStorage;

    public function testItServesTheRealImageToATrustedIpAddress(): void
    {
        $this->fileStorage->write('activities/photo.png', self::PNG_BYTES);

        $response = $this->securedImageRequestHandler->handle('activities/photo.png', $this->requestFromIp('192.168.1.1'));

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('image/png', $response->headers->get('Content-Type'));
        $this->assertEquals(self::PNG_BYTES, $this->captureStreamedContent($response));
    }

    public function testItServesAnAnonymizedImageToAnUntrustedIpAddress(): void
    {
        $this->fileStorage->write('activities/photo.png', self::PNG_BYTES, []);

        $response = $this->securedImageRequestHandler->handle('activities/photo.png', $this->requestFromIp('10.0.0.1'));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringStartsWith('https://picsum.photos/seed/photo/', $response->getTargetUrl());
    }

    public function testItServesAnAnonymizedImageWhenThereIsNoClientIpHeader(): void
    {
        $this->fileStorage->write('activities/photo.png', self::PNG_BYTES, []);

        $response = $this->securedImageRequestHandler->handle('activities/photo.png', new Request());

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringStartsWith('https://picsum.photos/seed/photo/', $response->getTargetUrl());
    }

    public function testItReturnsNotFoundWhenTheFileDoesNotExist(): void
    {
        $response = $this->securedImageRequestHandler->handle('activities/missing.png', $this->requestFromIp('192.168.1.1'));

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testItServesTheRealImageToEveryoneWhenNoAllowListIsConfigured(): void
    {
        $securedImageRequestHandler = new SecuredImageRequestHandler(
            $this->fileStorage,
            AllowedIpAddresses::fromString(''),
        );
        $this->fileStorage->write('activities/photo.png', self::PNG_BYTES, []);

        $response = $securedImageRequestHandler->handle('activities/photo.png', new Request());

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertEquals(self::PNG_BYTES, $this->captureStreamedContent($response));
    }

    #[DataProvider('provideOrientationSeeds')]
    public function testItPicksTheAnonymizedImageOrientationBasedOnTheSeed(string $fileName, string $expectedUrl): void
    {
        $this->fileStorage->write('activities/'.$fileName, self::PNG_BYTES, []);

        $response = $this->securedImageRequestHandler->handle('activities/'.$fileName, $this->requestFromIp('10.0.0.1'));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals($expectedUrl, $response->getTargetUrl());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideOrientationSeeds(): iterable
    {
        yield 'landscape' => ['landscape-photo.png', 'https://picsum.photos/seed/landscape-photo/1200/800'];
        yield 'portrait' => ['portrait-photo.png', 'https://picsum.photos/seed/portrait-photo/800/1200'];
    }

    private function requestFromIp(string $ipAddress): Request
    {
        $request = new Request();
        $request->headers->set('CF-Connecting-IP', $ipAddress);

        return $request;
    }

    private function captureStreamedContent(StreamedResponse $response): string
    {
        ob_start();
        $response->sendContent();

        return (string) ob_get_clean();
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->securedImageRequestHandler = new SecuredImageRequestHandler(
            $this->fileStorage = $this->getContainer()->get('file.storage'),
            AllowedIpAddresses::fromString('192.168.1.1'),
        );
    }
}
