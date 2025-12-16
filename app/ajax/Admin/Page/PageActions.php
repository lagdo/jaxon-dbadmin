<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Page;

use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\Ajax\Component;

#[Exclude]
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
