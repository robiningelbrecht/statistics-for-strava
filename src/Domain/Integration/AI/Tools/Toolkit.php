<?php

declare(strict_types=1);

namespace App\Domain\Integration\AI\Tools;

use NeuronAI\Tools\Toolkits\AbstractToolkit;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class Toolkit extends AbstractToolkit
{
    public function __construct(
        #[AutowireIterator('app.ai_tool')]
        private readonly iterable $tools,
    ) {
    }

    /**
     * @return \NeuronAI\Tools\ToolInterface[]
     */
    public function provide(): array
    {
        return iterator_to_array($this->tools);
    }
}
