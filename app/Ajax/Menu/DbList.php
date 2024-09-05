<?php

namespace Lagdo\DbAdmin\App\Ajax\Menu;

use Lagdo\DbAdmin\App\MenuComponent;

class DbList extends MenuComponent
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
        $this->response->jq('.')->val($database)->change();
    }
}
