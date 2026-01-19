<?php

namespace App\Tests\Controller;

use App\Controller\AIChatRequestHandler;
use App\Domain\Integration\AI\Chat\ChatCommands;
use App\Domain\Integration\AI\Chat\ChatMessage;
use App\Domain\Integration\AI\Chat\ChatMessageId;
use App\Domain\Integration\AI\Chat\ChatRepository;
use App\Infrastructure\Config\AppConfig;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\ValueObject\String\KernelProjectDir;
use App\Infrastructure\ValueObject\String\PlatformEnvironment;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use League\Flysystem\FilesystemOperator;
use NeuronAI\AgentInterface;
use NeuronAI\Chat\Enums\MessageRole;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;

class AIChatRequestHandlerTest extends ContainerTestCase
{
    use MatchesSnapshots;

    private FilesystemOperator $buildStorage;
    private Stub $neuronAIAgent;
    private MockObject $chatRepository;

    public function testHandle(): void
    {
        $this->buildStorage->write('index.html', 'I am the index', []);

        $this->chatRepository
            ->expects($this->once())
            ->method('findAll')
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
            ->method('findAll');

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
            ->method('findAll');

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

    public function testClearChat(): void
    {
        $requestHandler = $this->buildRequestHandler(
            $this->getContainer()->get(KernelProjectDir::class)->getForTestSuite('app-configs/config-ai-disabled')
        );

        $this->chatRepository
            ->expects($this->once())
            ->method('clear');

        $this->assertEquals(
            204,
            $requestHandler->clearChat()->getStatusCode()
        );
    }

    private function buildRequestHandler(KernelProjectDir $kernelProjectDir): AIChatRequestHandler
    {
        AppConfig::init(
            kernelProjectDir: $kernelProjectDir,
            platformEnvironment: PlatformEnvironment::PROD
        );

        return new AIChatRequestHandler(
            $this->buildStorage,
            $this->neuronAIAgent,
            ChatCommands::fromArray([]),
            $this->chatRepository,
            $this->getContainer()->get(CommandBus::class),
            $this->getContainer()->get(FormFactoryInterface::class),
            $this->getContainer()->get(Environment::class),
        );
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->buildStorage = $this->getContainer()->get('build.storage');
        $this->neuronAIAgent = $this->createStub(AgentInterface::class);
        $this->chatRepository = $this->createMock(ChatRepository::class);
    }
}
