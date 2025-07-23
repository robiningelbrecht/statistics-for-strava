<?php

namespace App\Tests\Controller;

use App\Controller\AIChatRequestHandler;
use App\Domain\Integration\AI\Chat\ChatMessage;
use App\Domain\Integration\AI\Chat\ChatMessageId;
use App\Domain\Integration\AI\Chat\ChatRepository;
use App\Infrastructure\Config\AppConfig;
use App\Infrastructure\ValueObject\String\KernelProjectDir;
use App\Infrastructure\ValueObject\String\PlatformEnvironment;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use League\Flysystem\FilesystemOperator;
use NeuronAI\AgentInterface;
use NeuronAI\Chat\Enums\MessageRole;
use PHPUnit\Framework\MockObject\MockObject;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;

class AIChatRequestHandlerTest extends ContainerTestCase
{
    use MatchesSnapshots;

    private FilesystemOperator $buildStorage;
    private MockObject $neuronAIAgent;
    private MockObject $chatRepository;

    public function testHandle(): void
    {
        $this->buildStorage->write('index.html', 'I am the index', []);

        $this->chatRepository
            ->expects($this->once())
            ->method('getHistory')
            ->willReturn([new ChatMessage(
                messageId: ChatMessageId::random(),
                message: 'message',
                messageRole: MessageRole::USER,
                on: SerializableDateTime::fromString('2025-05-05')
            )->withFirstLetterOfFirstName('R')]);

        $requestHandler = $this->buildRequestHandler(
            $this->getContainer()->get(KernelProjectDir::class)->getForTestSuite('app-configs/config-ai-enabled')
        );

        $this->assertMatchesHtmlSnapshot($requestHandler->handle(new Request(
            query: [],
            request: [],
            attributes: [],
            cookies: [],
            files: [],
            server: [],
            content: [],
        ))->getContent());
    }

    public function testHandleNoIndexFound(): void
    {
        $this->chatRepository
            ->expects($this->never())
            ->method('getHistory');

        $requestHandler = $this->buildRequestHandler(
            $this->getContainer()->get(KernelProjectDir::class)->getForTestSuite('app-configs/config-ai-enabled')
        );

        $this->assertMatchesHtmlSnapshot($requestHandler->handle(new Request(
            query: [],
            request: [],
            attributes: [],
            cookies: [],
            files: [],
            server: [],
            content: [],
        ))->getContent());
    }

    public function testHandleAINotEnabled(): void
    {
        $this->buildStorage->write('index.html', 'I am the index', []);

        $this->chatRepository
            ->expects($this->never())
            ->method('getHistory');

        $requestHandler = $this->buildRequestHandler(
            $this->getContainer()->get(KernelProjectDir::class)->getForTestSuite('app-configs/config-ai-disabled')
        );

        $this->assertMatchesHtmlSnapshot($requestHandler->handle(new Request(
            query: [],
            request: [],
            attributes: [],
            cookies: [],
            files: [],
            server: [],
            content: [],
        ))->getContent());
    }

    private function buildRequestHandler(KernelProjectDir $kernelProjectDir): AIChatRequestHandler
    {
        return new AIChatRequestHandler(
            $this->buildStorage,
            new AppConfig(
                kernelProjectDir: $kernelProjectDir,
                platformEnvironment: PlatformEnvironment::PROD
            ),
            $this->neuronAIAgent,
            $this->chatRepository,
            $this->getContainer()->get(FormFactoryInterface::class),
            $this->getContainer()->get(Environment::class),
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->buildStorage = $this->getContainer()->get('build.storage');
        $this->neuronAIAgent = $this->createMock(AgentInterface::class);
        $this->chatRepository = $this->createMock(ChatRepository::class);
    }
}
