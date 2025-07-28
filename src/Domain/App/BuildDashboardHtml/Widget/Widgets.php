<?php

declare(strict_types=1);

namespace App\Domain\App\BuildDashboardHtml\Widget;

use App\Domain\App\BuildDashboardHtml\DashboardLayout;
use App\Domain\App\BuildDashboardHtml\RenderedWidget;
use App\Infrastructure\Time\Clock\Clock;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class Widgets
{
    /** @var Widget[] */
    private array $widgets;

    /**
     * @param iterable<Widget> $widgets
     */
    public function __construct(
        #[AutowireIterator('app.dashboard.widget')]
        iterable $widgets,
        private readonly Clock $clock,
    ) {
        foreach ($widgets as $widget) {
            $widgetName = lcfirst(str_replace('Widget', '', new \ReflectionClass($widget)->getShortName()));
            $this->widgets[$widgetName] = $widget;
        }
    }

    /**
     * @return RenderedWidget[]
     */
    public function getRenderedWidgets(): array
    {
        $renderedWidgets = [];
        $layout = DashboardLayout::default();

        foreach ($layout as $widgetConfig) {
            if (!$widgetConfig['enabled']) {
                continue;
            }

            $widgetName = $widgetConfig['widget'];
            $widget = $this->widgets[$widgetName] ?? throw new \InvalidArgumentException(sprintf('Dashboard widget "%s" does not exists.', $widgetName));

            if (!$render = $widget->render($this->clock->getCurrentDateTimeImmutable())) {
                continue;
            }

            $renderedWidgets[] = new RenderedWidget(
                renderedHtml: $render,
                width: $widgetConfig['width']
            );
        }

        return $renderedWidgets;
    }
}
