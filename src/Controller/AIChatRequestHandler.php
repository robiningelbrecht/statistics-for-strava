<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Integration\AI\Chat\ChatRepository;
use App\Domain\Integration\AI\NeuronAIAgent;
use App\Infrastructure\Config\AppConfig;
use App\Infrastructure\Http\ServerSentEvent;
use League\Flysystem\FilesystemOperator;
use NeuronAI\Chat\Messages\UserMessage;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

#[AsController]
final readonly class AIChatRequestHandler
{
    public function __construct(
        private FilesystemOperator $buildStorage,
        private AppConfig $appConfig,
        private NeuronAIAgent $neuronAIAgent,
        private ChatRepository $chatRepository,
        private FormFactoryInterface $formFactory,
        private Environment $twig,
    ) {
    }

    #[Route(path: '/ai/chat', methods: ['GET'], priority: 2)]
    public function handle(Request $request): Response
    {
        if (!$this->buildStorage->fileExists('index.html')) {
            return new RedirectResponse('/', Response::HTTP_FOUND);
        }

        if (!$this->appConfig->AIIntegrationWithUiIsEnabled()) {
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
            'chatHistory' => $this->chatRepository->getHistory(),
            'form' => $form->createView(),
        ]), Response::HTTP_OK);
    }

    #[Route('/chat/sse', methods: ['GET'], priority: 2)]
    public function chatSse(Request $request): StreamedResponse
    {
        return new StreamedResponse(function () use ($request) {
            while (ob_get_level() > 0) {
                ob_end_flush();
            }
            ob_implicit_flush();

            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('X-Accel-Buffering: no');

            /** @var string $message */
            $message = $request->query->get('message');

            echo new ServerSentEvent(
                eventName: 'fullMessage',
                data: $this->twig->render('html/chat/message.html.twig', [
                    'chatMessage' => $this->chatRepository->create(
                        message: $message,
                        isUserMessage: true,
                    ),
                    'isThinking' => false,
                ])
            );

            echo new ServerSentEvent(
                eventName: 'fullMessage',
                data: $this->twig->render('html/chat/message.html.twig', [
                    'chatMessage' => $this->chatRepository->create(
                        message: '__PLACEHOLDER__',
                        isUserMessage: false,
                    ),
                    'isThinking' => true,
                ])
            );

            try {
                foreach ($this->neuronAIAgent->stream(new UserMessage($message)) as $chunk) {
                    echo new ServerSentEvent(
                        eventName: 'removeThinking',
                        data: ''
                    );

                    echo new ServerSentEvent(
                        eventName: 'agentResponse',
                        data: nl2br($chunk)
                    );
                    flush();
                }
            } catch (\Exception $e) {
                echo new ServerSentEvent(
                    eventName: 'removeThinking',
                    data: ''
                );

                echo new ServerSentEvent(
                    eventName: 'agentResponse',
                    data: 'Oh no, I made a booboo... <br />'.$e->getMessage()
                );
            }

            echo new ServerSentEvent(
                eventName: 'done',
                data: 'done'
            );
            flush();
        });
    }
}
