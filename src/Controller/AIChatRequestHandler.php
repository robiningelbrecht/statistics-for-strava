<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\App\ProfilePictureUrl;
use App\Infrastructure\Serialization\Json;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

#[AsController]
final readonly class AIChatRequestHandler
{
    public function __construct(
        private FilesystemOperator $buildStorage,
        private ?ProfilePictureUrl $profilePictureUrl,
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
            'profilePictureUrl' => $this->profilePictureUrl,
            'form' => $form->createView(),
        ]), Response::HTTP_OK);
    }

    #[Route(path: '/ai/chat/user-message', methods: ['POST'], priority: 2)]
    public function handleUserMessage(Request $request): Response
    {
        if (!$this->buildStorage->fileExists('index.html')) {
            return new RedirectResponse('/', Response::HTTP_FOUND);
        }

        $content = Json::decode($request->getContent());

        return new JsonResponse([
            'response' => $this->twig->render('html/chat/message.html.twig', [
                'profilePictureUrl' => $this->profilePictureUrl,
                'message' => $content['form[message]'],
            ]),
        ]);
    }
}
