<?php

namespace Lagdo\DbAdmin\App\Ajax\Menu\Server;

use Lagdo\DbAdmin\App\MenuComponent;

class Databases extends MenuComponent
{
    /**
     * @var array
     */
    private $databases = [];

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->ui->menuDatabases($this->databases);
    }

    /**
     * @exclude
     *
     * @param array $databases
     *
     * @return void
     */
    public function showDatabases(array $databases)
    {
        $this->databases = $databases;
        $this->render();
    }

    /**
     * @exclude
     *
     * @param string $database
     *
     * @return void
     */
    public function change(string $database)
    {
        // Change the value of the select field in the component content.
        $this->node()->jq('select')->val($database)->change();
    }
}
