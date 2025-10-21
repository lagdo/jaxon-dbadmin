<?php

namespace Lagdo\DbAdmin\Ajax\App;

use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\Ajax\Component;

#[Exclude]
class Sidebar extends Component
{
    /**
     * @inheritDoc
     */
    public function html(): string
    {
        $servers = $this->package()->getOption('servers', []);
        $serverAccess = $this->package()->getOption('access.server', false);
        $default = $this->package()->getOption('default', '');
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
