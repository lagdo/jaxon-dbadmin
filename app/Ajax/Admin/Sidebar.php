<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin;

use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\App\Ajax\Base\Component;
use Lagdo\DbAdmin\App\Ui\UiBuilder;

#[Exclude]
class Sidebar extends Component
{
    /**
     * @inheritDoc
     */
    public function html(): string
    {
        $servers = $this->config()->getServerNames();
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
        $this->node()->jq('#' . UiBuilder::hostSelectId())->val($server)->change();
    }
}
