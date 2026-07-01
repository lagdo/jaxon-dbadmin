<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin;

use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\App\Ajax\Base\Component;

#[Exclude]
class Sidebar extends Component
{
    /**
     * @return string
     */
    private function header(): string
    {
        $servers = $this->config()->getServerNames();
        $default = $this->config()->getOption('default', '');
        return $this->ui()->sidebarHeader($servers, $default);
    }

    /**
     * @return string
     */
    private function content(): string
    {
        $serverAccess = $this->config()->getOption('access.server', false);
        return $this->ui()->sidebarContent($serverAccess);
    }

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->get('header', true) ? $this->header() : $this->content();
    }

    /**
     * @param string $server
     *
     * @return void
     */
    public function refresh(string $server): void
    {
        $this->set('header', true);
        $this->item('header')->render();
        // Change the value of the select field in the component content.
        $this->node()->jq('#' . $this->ui()->hostSelectId())->val($server)->change();

        $this->set('header', false);
        $this->item('content')->render();
    }
}
