<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\Widget;

use App\Domain\Dashboard\DashboardLayout;
use App\Domain\Dashboard\RenderedWidget;
use App\Infrastructure\Time\Clock\Clock;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class Widgets implements \IteratorAggregate
{
    /** @var Widget[] */
    private array $widgets;

    /**
     * @param iterable<Widget> $widgets
     */
    public function __construct(
        #[AutowireIterator('app.dashboard.widget')]
        iterable $widgets,
        private readonly DashboardLayout $dashboardLayout,
        private readonly Clock $clock,
    ) {
        foreach ($widgets as $widget) {
            $widgetName = WidgetName::fromWidgetInstance($widget);
            $this->widgets[(string) $widgetName] = $widget;
        }
    }

    public function getIterator(): \Traversable
    {
        $renderedWidgets = [];
        foreach ($this->dashboardLayout as $configuredWidget) {
            if (!$configuredWidget['enabled']) {
                continue;
            }

            $widgetName = WidgetName::fromConfigValue($configuredWidget['widget']);
            if ($widgetName->wasRemoved()) {
                continue;
            }
            $widget = $this->widgets[(string) $widgetName] ?? throw new \InvalidArgumentException(sprintf('Dashboard widget "%s" does not exists.', $widgetName));

            $widget->guardValidConfiguration($configuredWidget['config'] ?? []);
            $widgetConfig = $widget->getDefaultConfiguration();

            foreach ($configuredWidget['config'] ?? [] as $key => $value) {
                $widgetConfig->add($key, $value);
            }

            if (!$render = $widget->render($this->clock->getCurrentDateTimeImmutable(), $widgetConfig)) {
                continue;
            }

            $renderedWidgets[] = new RenderedWidget(
                renderedHtml: $render,
                width: $configuredWidget['width']
            );
        }

        return new \ArrayIterator($renderedWidgets);
    }
}
