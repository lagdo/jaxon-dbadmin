<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Menu\Database;

use Lagdo\DbAdmin\Ajax\Base\MenuComponent;

class Schemas extends MenuComponent
{
    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->ui()->schemas($this->get('database'), $this->get('schemas'));
    }
}
