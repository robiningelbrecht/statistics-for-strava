<?php

namespace App\Tests\Controller\Admin;

use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\Serialization\Json;
use App\Tests\Infrastructure\CQRS\Command\Bus\SpyCommandBus;
use App\Tests\Infrastructure\CQRS\Command\Deserialize\TestDeserializableCommand;
use Symfony\Component\HttpFoundation\Response;

class DispatchCommandRequestHandlerTest extends AdminWebTestCase
{
    public function testAnonymousUsersAreRedirectedToTheLoginPage(): void
    {
        $this->client->request('POST', '/admin/dispatchCommand');

        $this->assertResponseRedirects('/admin/login');
    }

    public function testHandle(): void
    {
        $this->client->loginUser($this->adminUser());
        $this->client->disableReboot();

        $spyCommandBus = new SpyCommandBus();
        static::getContainer()->set(CommandBus::class, $spyCommandBus);

        $this->client->request(
            method: 'POST',
            uri: '/admin/dispatchCommand',
            server: ['HTTP_X_CSRF_TOKEN' => $this->validCsrfToken()],
            content: Json::encode([
                'commandName' => TestDeserializableCommand::class,
                'payload' => [
                    'message' => 'Hello',
                    'url' => 'https://example.com',
                ],
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $dispatchedCommands = $spyCommandBus->getDispatchedCommands();
        $this->assertCount(1, $dispatchedCommands);
        $this->assertInstanceOf(TestDeserializableCommand::class, $dispatchedCommands[0]);
    }

    public function testHandleWithInvalidCsrfToken(): void
    {
        $this->client->loginUser($this->adminUser());

        $this->client->request(
            method: 'POST',
            uri: '/admin/dispatchCommand',
            server: ['HTTP_X_CSRF_TOKEN' => 'a-tampered-token'],
            content: Json::encode([
                'commandName' => TestDeserializableCommand::class,
                'payload' => [
                    'message' => 'Hello',
                    'url' => 'https://example.com',
                ],
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testHandleWithInvalidContent(): void
    {
        $this->client->loginUser($this->adminUser());
        $this->client->disableReboot();

        $this->client->request(
            method: 'POST',
            uri: '/admin/dispatchCommand',
            server: ['HTTP_X_CSRF_TOKEN' => $this->validCsrfToken()],
            content: Json::encode(['not' => 'a command']),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    private function validCsrfToken(): string
    {
        $crawler = $this->client->request('GET', '/admin/upload');

        return $crawler->filter('meta[name="csrf-token"]')->attr('content');
    }
}
