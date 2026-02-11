<?php

declare(strict_types=1);

namespace App\Infrastructure\Twig;

use App\Infrastructure\Localisation\TranslatableWithDescription;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Attribute\AsTwigFilter;

final readonly class TranslationTwigExtension
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    #[AsTwigFilter('transDescription')]
    public function transDescription(TranslatableWithDescription $message): string
    {
        if ($message instanceof TranslatableMessage && '' === $message->getMessage()) {
            return '';
        }

        return $message->transDescription($this->translator);
    }
}
