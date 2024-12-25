<?php

namespace Lagdo\DbAdmin\App\Ajax\Menu\Database;

use Lagdo\DbAdmin\App\MenuComponent;

class Schemas extends MenuComponent
{
    /**
     * @var string
     */
    private $database = '';

    /**
     * @var array
     */
    private $schemas = [];

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->ui->menuSchemas($this->database, $this->schemas);
    }

    /**
     * @exclude
     *
     * @param string $database
     * @param array $schemas
     *
     * @return void
     */
    public function showDbSchemas(string $database, array $schemas)
    {
        $this->database = $database;
        $this->schemas = $schemas;
        $this->render();
    }
}
