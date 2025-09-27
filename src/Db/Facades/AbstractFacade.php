<?php

namespace Lagdo\DbAdmin\Db\Facades;

use Lagdo\DbAdmin\Admin\Admin;
use Lagdo\DbAdmin\Db\AppDriver;
use Lagdo\DbAdmin\Driver\Utils\Utils;

/**
 * Common attributes for all facades
 */
class AbstractFacade
{
    /**
     * @var AppDriver|null
     */
    protected AppDriver|null $driver = null;

    /**
     * @var Admin
     */
    protected Admin $admin;

    /**
     * @var Utils
     */
    protected Utils $utils;

    /**
     * Initialize the facade
     *
     * @param AbstractFacade $dbFacade
     */
    public function __construct(AbstractFacade $dbFacade)
    {
        $this->driver = $dbFacade->driver;
        $this->admin = $dbFacade->admin;
        $this->utils = $dbFacade->utils;
    }
}
