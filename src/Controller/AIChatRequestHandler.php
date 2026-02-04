<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Integration\AI\Chat\AddChatMessage\AddChatMessage;
use App\Domain\Integration\AI\Chat\ChatCommands;
use App\Domain\Integration\AI\Chat\ChatRepository;
use App\Infrastructure\Config\AppConfig;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\Http\ServerSentEvent;
use App\Infrastructure\Serialization\Json;
use GuzzleHttp\Exception\ClientException;
use League\Flysystem\FilesystemOperator;
use NeuronAI\AgentInterface;
use NeuronAI\Chat\Enums\MessageRole;
use NeuronAI\Chat\Messages\ToolCallMessage;
use NeuronAI\Chat\Messages\ToolCallResultMessage;
use NeuronAI\Chat\Messages\UserMessage;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\EventStreamResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

#[AsController]
final readonly class AIChatRequestHandler
{
    public function __construct(
        private FilesystemOperator $buildStorage,
        private AgentInterface $neuronAIAgent,
        private ChatCommands $chatCommands,
        private ChatRepository $chatRepository,
        private CommandBus $commandBus,
        private FormFactoryInterface $formFactory,
        private Environment $twig,
    ) {
    }

    #[Route(path: '/ai/chat', methods: ['GET'], priority: 2)]
    public function handle(): Response
    {
        if (!$this->buildStorage->fileExists('index.html')) {
            return new RedirectResponse('/', Response::HTTP_FOUND);
        }
        if (!AppConfig::isAIIntegrationWithUIEnabled()) {
            return new Response('UI for AI not enabled', Response::HTTP_OK);
        }
        $formBuilder = $this->formFactory->createBuilder();
        $form = $formBuilder
            ->setAction('/ai/chat/user-message')
            ->add('message', TextType::class, [
                'label' => 'Message',
                'required' => true,
            ])
            ->add('submit', SubmitType::class)
            ->getForm();

        return new Response($this->twig->render('html/chat/chat.html.twig', [
            'chatHistory' => $this->chatRepository->findAll(),
            'form' => $form->createView(),
            'chatCommands' => Json::encode($this->chatCommands),
        ]), Response::HTTP_OK);
    }

    #[Route(path: '/chat/clear', methods: ['POST'], priority: 2)]
    public function clearChat(): Response
    {
        $this->chatRepository->clear();

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    /**
     * @codeCoverageIgnore
     */
    #[Route('/chat/sse', methods: ['GET'], priority: 2)]
    public function chatSse(Request $request): EventStreamResponse
    {
        return new EventStreamResponse(function (EventStreamResponse $response) use ($request): void {
            /** @var string $message */
            $message = $request->query->get('message');

            $response->sendEvent(new ServerSentEvent(
                data: $this->twig->render('html/chat/message.html.twig', [
                    'chatMessage' => $this->chatRepository->buildMessage(
                        message: $message,
                        messageRole: MessageRole::USER,
                    ),
                    'isThinking' => false,
                ]),
                type: 'fullMessage'
            ));

            $response->sendEvent(new ServerSentEvent(
                data: $this->twig->render('html/chat/message.html.twig', [
                    'chatMessage' => $this->chatRepository->buildMessage(
                        message: '__PLACEHOLDER__',
                        messageRole: MessageRole::ASSISTANT,
                    ),
                    'isThinking' => true,
                ]),
                type: 'fullMessage'
            ));

            try {
                foreach ($this->neuronAIAgent->stream(new UserMessage($message)) as $chunk) {
                    if ($chunk instanceof ToolCallMessage) {
                        continue;
                    }
                    if ($chunk instanceof ToolCallResultMessage) {
                        continue;
                    }
                    $response->sendEvent(new ServerSentEvent(
                        data: '',
                        type: 'removeThinking'
                    ));

                    $response->sendEvent(new ServerSentEvent(
                        data: $chunk,
                        type: 'agentResponse'
                    ));
                }
            } catch (\Throwable $e) {
                $response->sendEvent(new ServerSentEvent(
                    data: '',
                    type: 'removeThinking'
                ));

                $message = $e->getMessage().': '.$e->getTraceAsString();
                if ($e instanceof ClientException) {
                    $message = $e->getResponse()->getBody()->getContents();
                }

                $fullMessage = 'Oh no, I made a booboo... <br />'.preg_replace('/\s+/', ' ', $message);

                $response->sendEvent(new ServerSentEvent(
                    data: $fullMessage,
                    type: 'agentResponse'
                ));

                $this->commandBus->dispatch(new AddChatMessage(
                    message: $fullMessage,
                    messageRole: MessageRole::ASSISTANT,
                ));
            }

            $response->sendEvent(new ServerSentEvent(
                data: 'done',
                type: 'done'
            ));
        });
    }
}
