<?php namespace October\Debugbar\DataCollectors;

use Cms\Classes\ComponentBase;
use Cms\Classes\Controller;
use Cms\Classes\Layout;
use Cms\Classes\Page;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;

/**
 * OctoberComponentsCollector collects component details from the page and layout
 */
class OctoberComponentsCollector extends DataCollector implements Renderable
{
    /** @var Controller */
    protected $controller;

    /** @var Page */
    protected $page;

    /** @var Layout */
    protected $layout;

    /**
     * __construct
     */
    public function __construct(Controller $controller, Page $page, Layout $layout)
    {
        $this->controller = $controller;
        $this->page = $page;
        $this->layout = $layout;
    }

    /**
     * {@inheritDoc}
     */
    public function collect(): array
    {
        $components = [];

        foreach ($this->layout->components as $alias => $componentObj) {
            $components[$alias] = $this->formatComponent($componentObj);
        }

        foreach ($this->page->components as $alias => $componentObj) {
            $components[$alias] = $this->formatComponent($componentObj);
        }

        return $components;
    }

    /**
     * formatComponent builds a readable string to describe a component
     */
    protected function formatComponent($componentObj): string
    {
        $class = get_class($componentObj);
        $props = $componentObj->getProperties();

        if (empty($props)) {
            return $class;
        }

        $parts = [];
        foreach ($props as $key => $value) {
            $parts[] = $key . '=' . (is_scalar($value) ? var_export($value, true) : gettype($value));
        }

        return $class . ' (' . implode(', ', $parts) . ')';
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'components';
    }

    /**
     * {@inheritDoc}
     */
    public function getWidgets(): array
    {
        return [
            "components" => [
                "icon" => "puzzle-piece",
                "widget" => "PhpDebugBar.Widgets.VariableListWidget",
                "map" => "components",
                "default" => "{}"
            ]
        ];
    }
}
