<?php

namespace Lagdo\DbAdmin\App\Ajax\Menu;

use Jaxon\App\Component;
use Lagdo\DbAdmin\Ui\MenuBuilder;

class DbList extends Component
{
    /**
     * @var array
     */
    private $databases = [];

    /**
     * @param MenuBuilder $ui
     */
    public function __construct(private MenuBuilder $ui)
    {}

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
    public function update(array $databases)
    {
        $this->databases = $databases;
        $this->refresh();
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
        $this->jq()->val($database)->change();
    }
}
