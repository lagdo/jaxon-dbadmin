<?php

namespace Lagdo\DbAdmin\Db\Traits;

use Jaxon\Di\Container;

trait AbstractTrait
{
    /**
     * @return Container
     */
    abstract public function di(): Container;

    /**
     * Connect to a database server
     *
     * @return void
     */
    abstract public function connectToServer();

    /**
     * Connect to a database server
     *
     * @return void
     */
    abstract public function connectToDatabase();

    /**
     * Connect to a database server
     *
     * @return void
     */
    abstract public function connectToSchema();

    /**
     * Set the breadcrumbs items
     *
     * @param bool $showDatabase
     * @param array $breadcrumbs
     *
     * @return void
     */
    abstract protected function setBreadcrumbs(bool $showDatabase = false, array $breadcrumbs = []);
}
