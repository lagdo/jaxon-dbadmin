<?php

namespace Lagdo\DbAdmin\Ajax\App\Page;

use Jaxon\App\Component;
use Lagdo\DbAdmin\Translator;
use Lagdo\DbAdmin\Ui\PageBuilder;

class PageActions extends Component
{
    /**
     * @var array
     */
    private $actions = [];

    /**
     * @param PageBuilder $ui
     * @param Translator $trans
     */
    public function __construct(private PageBuilder $ui, private Translator $trans)
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
     * @return void
     */
    public function show(array $actions)
    {
        $this->actions = $actions;
        $this->render();
    }
}
