<?php

namespace Lagdo\DbAdmin\Ajax\App\Menu\Server;

use Lagdo\DbAdmin\Ajax\MenuComponent;

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
        return $this->ui()->menuDatabases($this->databases);
    }

    /**
     * @exclude
     *
     * @param array $databases
     *
     * @return void
     */
    public function showDatabases(array $databases): void
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
    public function change(string $database): void
    {
        // Change the value of the select field in the component content.
        $this->node()->jq('select')->val($database)->change();
    }
}
