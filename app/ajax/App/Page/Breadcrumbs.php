<?php

namespace Lagdo\DbAdmin\Ajax\App\Page;

use Lagdo\DbAdmin\Ajax\Component;

use function array_merge;

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
        [$server,] = $this->bag('dbadmin')->get('db');
        $serverName = $this->package->getServerName($server);
        $breadcrumbs = array_merge([$serverName], $this->db->breadcrumbs()->items());
        return $this->ui()->breadcrumbs($breadcrumbs);
    }
}
