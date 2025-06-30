<?php

namespace Lagdo\DbAdmin\Ajax\App;

use Lagdo\DbAdmin\Ajax\Component;

/**
 * @exclude
 */
class Sidebar extends Component
{
    /**
     * @inheritDoc
     */
    public function html(): string
    {
        $servers = $this->package()->getOption('servers', []);
        $default = $this->package()->getOption('default', '');
        return $this->ui()->sidebar($servers, $default);
    }
}
