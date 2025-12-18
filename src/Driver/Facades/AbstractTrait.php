<?php

namespace Lagdo\DbAdmin\Db\Driver\Facades;

use Jaxon\Di\Container;
use Lagdo\DbAdmin\Db\Service\Breadcrumbs;

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
     * Get the breadcrumbs object
     *
     * @param bool $withDb
     *
     * @return Breadcrumbs
     */
    abstract public function breadcrumbs(bool $withDb = false): Breadcrumbs;
}
