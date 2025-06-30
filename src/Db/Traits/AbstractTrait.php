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
     * Clear the breadcrumbs
     *
     * @return self
     */
    abstract protected function bccl(): self;

    /**
     * Add the selected DB name to the breadcrumbs
     *
     * @return self
     */
    abstract protected function bcdb(): self;

    /**
     * Add an item to the breadcrumbs
     *
     * @param string $label
     *
     * @return self
     */
    abstract protected function breadcrumb(string $label): self;
}
