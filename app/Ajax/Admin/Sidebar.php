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
     * @return string
     */
    private function toggleButton(): string
    {
        $visible = $this->getBag('dbadmin.tab', 'sidebar.visible', true);
        return $this->ui()->sidebarToggleButton($visible);
    }

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return match($this->get('item', 'content')) {
            'header' => $this->header(),
            'toggle' => $this->toggleButton(),
            default => $this->content(),
        };
    }

    /**
     * @param string $server
     *
     * @return void
     */
    public function refresh(string $server): void
    {
        $this->set('item', 'header');
        $this->item('header')->render();
        // Change the value of the select field in the component content.
        $this->node()->jq('#' . $this->ui()->hostSelectId())->val($server)->change();

        $this->set('item', 'content');
        $this->item('content')->render();
    }

    /**
     * @param bool $visible
     *
     * @return void
     */
    public function toggle(bool $visible): void
    {
        $this->item('header')->visible($visible);
        $this->item('wrapper')->visible($visible);

        $this->set('item', 'toggle');
        $this->item('toggle')->render();
    }
}
