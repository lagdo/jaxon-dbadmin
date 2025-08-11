<?php

namespace Lagdo\DbAdmin\Ajax\App\Page;

use Lagdo\DbAdmin\Ajax\Component;

/**
 * @exclude
 */
class Breadcrumbs extends Component
{
    /**
     * @var array
     */
    private $breadcrumbs = [];

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->ui()->breadcrumbs($this->db->getBreadcrumbs());
    }
}
