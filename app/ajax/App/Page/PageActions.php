<?php

namespace Lagdo\DbAdmin\Ajax\App\Page;

use Lagdo\DbAdmin\Ajax\Component;

/**
 * @exclude
 */
class PageActions extends Component
{
    /**
     * @var array
     */
    private $actions = [];

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->ui()->actions($this->actions);
    }

    public function show(array $actions): void
    {
        $this->actions = $actions;
        $this->render();
    }
}
