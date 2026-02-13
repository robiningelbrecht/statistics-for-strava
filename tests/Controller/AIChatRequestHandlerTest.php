<?php

namespace App\Tests\Controller;

use App\Controller\AIChatRequestHandler;
use App\Domain\Athlete\Athlete;
use App\Domain\Athlete\AthleteRepository;
use App\Domain\Integration\AI\Chat\AddChatMessage\AddChatMessage;
use App\Domain\Integration\AI\Chat\ChatCommands;
use App\Domain\Integration\AI\Chat\ChatMessage;
use App\Domain\Integration\AI\Chat\ChatMessageId;
use App\Domain\Integration\AI\Chat\ChatRepository;
use App\Domain\Integration\AI\Chat\DbalChatRepository;
use App\Infrastructure\Config\AppConfig;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\ValueObject\String\KernelProjectDir;
use App\Infrastructure\ValueObject\String\PlatformEnvironment;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use App\Tests\Infrastructure\CQRS\Command\Bus\SpyCommandBus;
use App\Tests\Infrastructure\Time\Clock\PausedClock;
use League\Flysystem\FilesystemOperator;
use NeuronAI\Agent;
use NeuronAI\AgentInterface;
use NeuronAI\Chat\Enums\MessageRole;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Testing\FakeAIProvider;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\EventStreamResponse;
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

        $this->assertMatchesHtmlSnapshot($requestHandler->handle()->getContent());
    }

    public function testHandleNoIndexFound(): void
    {
        $this->chatRepository
            ->expects($this->never())
            ->method('findAll');

        $requestHandler = $this->buildRequestHandler(
            $this->getContainer()->get(KernelProjectDir::class)->getForTestSuite('app-configs/config-ai-enabled')
        );

        $this->assertMatchesHtmlSnapshot($requestHandler->handle()->getContent());
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

        $this->assertMatchesHtmlSnapshot($requestHandler->handle()->getContent());
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

    #[AllowMockObjectsWithoutExpectations]
    public function testChatSse(): void
    {
        $this->getContainer()->get(AthleteRepository::class)->save(Athlete::create([
            'id' => 100,
            'birthDate' => '1989-08-14',
            'firstname' => 'robin',
        ]));

        $chatRepository = new DbalChatRepository(
            connection: $this->getConnection(),
            profilePictureUrl: null,
            athleteRepository: $this->getContainer()->get(AthleteRepository::class),
            clock: PausedClock::on(SerializableDateTime::fromString('2025-05-05')),
        );

        $agent = Agent::make()->setAiProvider(
            new FakeAIProvider(new AssistantMessage('Hello World'))
        );

        $requestHandler = $this->buildRequestHandlerForSse(
            chatRepository: $chatRepository,
            agent: $agent,
            commandBus: new SpyCommandBus(),
        );

        $request = new Request(query: ['message' => 'What is my FTP?']);
        $response = $requestHandler->chatSse($request);

        $this->assertInstanceOf(EventStreamResponse::class, $response);

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString('event: fullMessage', $content);
        $this->assertStringContainsString('event: removeThinking', $content);
        $this->assertStringContainsString('event: agentResponse', $content);
        $this->assertStringContainsString('Hello', $content);
        $this->assertStringContainsString('event: done', $content);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testChatSseOnError(): void
    {
        $this->getContainer()->get(AthleteRepository::class)->save(Athlete::create([
            'id' => 100,
            'birthDate' => '1989-08-14',
            'firstname' => 'robin',
        ]));

        $chatRepository = new DbalChatRepository(
            connection: $this->getConnection(),
            profilePictureUrl: null,
            athleteRepository: $this->getContainer()->get(AthleteRepository::class),
            clock: PausedClock::on(SerializableDateTime::fromString('2025-05-05')),
        );

        $agent = Agent::make()->withProvider(
            new FakeAIProvider()
        );

        $spyCommandBus = new SpyCommandBus();

        $requestHandler = $this->buildRequestHandlerForSse(
            chatRepository: $chatRepository,
            agent: $agent,
            commandBus: $spyCommandBus,
        );

        $request = new Request(query: ['message' => 'What is my FTP?']);
        $response = $requestHandler->chatSse($request);

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString('event: fullMessage', $content);
        $this->assertStringContainsString('event: removeThinking', $content);
        $this->assertStringContainsString('Oh no, I made a booboo', $content);
        $this->assertStringContainsString('event: done', $content);

        $dispatchedCommands = $spyCommandBus->getDispatchedCommands();
        $this->assertCount(1, $dispatchedCommands);
        $this->assertInstanceOf(AddChatMessage::class, $dispatchedCommands[0]);
    }

    private function buildRequestHandler(KernelProjectDir $kernelProjectDir): AIChatRequestHandler
    {
        AppConfig::init(
            kernelProjectDir: $kernelProjectDir,
            platformEnvironment: PlatformEnvironment::PROD
        );

        return new AIChatRequestHandler(
            buildStorage: $this->buildStorage,
            neuronAIAgent: $this->neuronAIAgent,
            chatCommands: ChatCommands::fromArray([]),
            chatRepository: $this->chatRepository,
            commandBus: $this->getContainer()->get(CommandBus::class),
            formFactory: $this->getContainer()->get(FormFactoryInterface::class),
            twig: $this->getContainer()->get(Environment::class),
        );
    }

    private function buildRequestHandlerForSse(
        DbalChatRepository $chatRepository,
        AgentInterface $agent,
        CommandBus $commandBus,
    ): AIChatRequestHandler {
        AppConfig::init(
            kernelProjectDir: $this->getContainer()->get(KernelProjectDir::class)->getForTestSuite('app-configs/config-ai-enabled'),
            platformEnvironment: PlatformEnvironment::PROD
        );

        return new AIChatRequestHandler(
            buildStorage: $this->buildStorage,
            neuronAIAgent: $agent,
            chatCommands: ChatCommands::fromArray([]),
            chatRepository: $chatRepository,
            commandBus: $commandBus,
            formFactory: $this->getContainer()->get(FormFactoryInterface::class),
            twig: $this->getContainer()->get(Environment::class),
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
