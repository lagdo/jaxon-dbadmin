<?php

namespace Lagdo\DbAdmin\Ajax\Admin;

use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\Ajax\Base\Component;

#[Exclude]
class Sidebar extends Component
{
    /**
     * @inheritDoc
     */
    public function html(): string
    {
        $servers = $this->config()->getServers();
        $serverAccess = $this->config()->getOption('access.server', false);
        $default = $this->config()->getOption('default', '');
        return $this->ui()->sidebar($servers, $serverAccess, $default);
    }

    /**
     * @param string $server
     *
     * @return void
     */
    public function refresh(string $server): void
    {
        $this->render();
        // Change the value of the select field in the component content.
        $this->node()->jq('#jaxon-dbadmin-dbhost-select')->val($server)->change();
    }
}
