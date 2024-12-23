<?php

namespace Lagdo\DbAdmin\App\Ajax\Page;

use Jaxon\App\Component;
use Lagdo\DbAdmin\Db\DbFacade;
use Lagdo\DbAdmin\App\Ui\PageBuilder;

class Breadcrumbs extends Component
{
    /**
     * @var array
     */
    private $breadcrumbs = [];

    /**
     * @param PageBuilder $ui
     * @param DbFacade $db
     */
    public function __construct(private PageBuilder $ui, private DbFacade $db)
    {}

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->ui->breadcrumbs($this->db->getBreadcrumbs());
    }
}
