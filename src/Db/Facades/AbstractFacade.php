<?php

namespace Lagdo\DbAdmin\Db\Facades;

use Lagdo\DbAdmin\Admin\Admin;
use Lagdo\DbAdmin\Driver\DriverInterface;
use Lagdo\DbAdmin\Driver\Utils\Utils;
use Lagdo\DbAdmin\Package;

/**
 * Common attributes for all facades
 */
class AbstractFacade
{
    /**
     * @var DriverInterface
     */
    protected $driver = null;

    /**
     * @var Admin
     */
    protected $admin = null;

    /**
     * @var Utils
     */
    protected $utils = null;

    /**
     * The Jaxon DbAdmin package
     *
     * @var Package
     */
    protected $package;

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
