<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Page;

use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\Ajax\Component;

use function array_merge;

#[Exclude]
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
        [$server,] = $this->bag('dbadmin')->get('db');
        $serverName = $this->package->getServerName($server);
        $breadcrumbs = array_merge([$serverName], $this->db->breadcrumbs()->items());
        return $this->ui()->breadcrumbs($breadcrumbs);
    }
}
