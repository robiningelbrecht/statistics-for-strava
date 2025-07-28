<?php

declare(strict_types=1);

namespace App\Domain\App\BuildDashboardHtml\Widget;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class Widgets
{
    /**
     * @param iterable<Widget> $widgets
     */
    public function __construct(
        #[AutowireIterator('app.dashboard.widget')]
        private iterable $widgets,
    ) {
    }

    public function getWidget(string $widgetName): Widget
    {
        foreach ($this->widgets as $widget) {
            if (new \ReflectionClass($widget)->getShortName() === $widgetName) {
                return $widget;
            }
        }

        throw new \InvalidArgumentException(sprintf('Dashboard widget "%s" does not exists.', $widgetName));
    }
}
