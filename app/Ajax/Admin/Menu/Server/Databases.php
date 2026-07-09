<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Menu\Server;

use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\App\Ajax\Base\MenuComponent;

class Databases extends MenuComponent
{
    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->ui()->databases($this->get('databases'), $this->get('selected', null));
    }

    /**
     * @param string $database
     *
     * @return void
     */
    #[Exclude]
    public function change(string $database): void
    {
        $systemAccess = $this->config()->getOption('access.system', false);
        $databases = $this->driver()->getDatabases($systemAccess)['databases'];
        $this->set('databases', $databases)->set('selected', $database)->render();

        // Change the value of the select field in the component content.
        // $this->node()->jq('select')->val($database)->change();
    }
}
