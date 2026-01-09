<?php

namespace Lagdo\DbAdmin\Db\Driver\Facades;

use Lagdo\DbAdmin\Db\Driver\AppDriver;
use Lagdo\DbAdmin\Db\UiData\AppPage;
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
     * @var AppPage
     */
    protected AppPage $page;

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
        $this->page = $dbFacade->page;
        $this->utils = $dbFacade->utils;
    }
}
