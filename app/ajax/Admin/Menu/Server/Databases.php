<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Menu\Server;

use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\Ajax\Base\MenuComponent;

class Databases extends MenuComponent
{
    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->ui()->databases($this->get('databases'));
    }

    /**
     * @param string $database
     *
     * @return void
     */
    #[Exclude]
    public function change(string $database): void
    {
        // Change the value of the select field in the component content.
        $this->node()->jq('select')->val($database)->change();
    }
}
