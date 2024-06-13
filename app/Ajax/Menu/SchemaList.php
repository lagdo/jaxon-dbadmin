<?php

namespace Lagdo\DbAdmin\App\Ajax\Menu;

use Jaxon\App\Component;
use Lagdo\DbAdmin\Ui\MenuBuilder;

class SchemaList extends Component
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
     * @param MenuBuilder $ui
     */
    public function __construct(private MenuBuilder $ui)
    {}

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
    public function update(string $database, array $schemas)
    {
        $this->database = $database;
        $this->schemas = $schemas;
        $this->refresh();
    }
}
