<?php

namespace Lagdo\DbAdmin\Db\Facades;

use Lagdo\DbAdmin\Admin\Admin;
use Lagdo\DbAdmin\DbAdminPackage;
use Lagdo\DbAdmin\Db\CallbackDriver;
use Lagdo\DbAdmin\Driver\Utils\Utils;

/**
 * Common attributes for all facades
 */
class AbstractFacade
{
    /**
     * @var CallbackDriver|null
     */
    protected CallbackDriver|null $driver = null;

    /**
     * @var Admin
     */
    protected Admin $admin;

    /**
     * @var Utils
     */
    protected Utils $utils;

    /**
     * The Jaxon DbAdmin package
     *
     * @var DbAdminPackage
     */
    protected DbAdminPackage $package;

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
        $this->package = $dbFacade->package;
    }
}
