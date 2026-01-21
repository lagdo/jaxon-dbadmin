<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Page;

use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\Ajax\Component;

#[Exclude]
class Breadcrumbs extends Component
{
    /**
     * @inheritDoc
     */
    public function html(): string
    {
        [$server,] = $this->bag('dbadmin')->get('db');
        $serverName = $this->config()->getServerName($server);
        $breadcrumbs = [$serverName, ...$this->db->breadcrumbs()->items()];
        return $this->ui()->breadcrumbs($breadcrumbs);
    }
}
