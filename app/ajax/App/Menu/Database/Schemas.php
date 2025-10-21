<?php

namespace Lagdo\DbAdmin\Ajax\App\Menu\Database;

use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\Ajax\MenuComponent;

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
        return $this->ui()->schemas($this->database, $this->schemas);
    }

    /**
     * @param string $database
     * @param array $schemas
     *
     * @return void
     */
    #[Exclude]
    public function showDbSchemas(string $database, array $schemas): void
    {
        $this->database = $database;
        $this->schemas = $schemas;
        $this->render();
    }
}
