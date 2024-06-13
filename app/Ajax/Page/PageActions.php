<?php

namespace Lagdo\DbAdmin\App\Ajax\Page;

use Jaxon\App\Component;
use Lagdo\DbAdmin\Ui\PageBuilder;

class PageActions extends Component
{
    /**
     * @var array
     */
    private $actions;

    /**
     * @param PageBuilder $ui
     */
    public function __construct(private PageBuilder $ui)
    {}

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->ui->pageActions($this->actions);
    }

    /**
     * @exclude
     *
     * @param array $actions
     *
     * @return void
     */
    public function update(array $actions)
    {
        $this->actions = $actions;
        $this->refresh();
    }
}
