<?php

namespace Lagdo\DbAdmin;

use Lagdo\DbAdmin\Driver\DriverInterface;
use Lagdo\DbAdmin\Driver\UtilInterface;

use Exception;

/**
 * Admin to calls to the database functions
 */
class DbAdmin extends DbAdmin\AbstractAdmin
{
    use DbAdmin\ServerTrait;
    use DbAdmin\UserTrait;
    use DbAdmin\DatabaseTrait;
    use DbAdmin\TableTrait;
    use DbAdmin\TableSelectTrait;
    use DbAdmin\TableQueryTrait;
    use DbAdmin\ViewTrait;
    use DbAdmin\CommandTrait;
    use DbAdmin\ExportTrait;
    use DbAdmin\ImportTrait;

    /**
     * The breadcrumbs items
     *
     * @var array
     */
    protected $breadcrumbs = [];

    /**
     * The Jaxon Adminer package
     *
     * @var Package
     */
    protected $package;

    /**
     * The constructor
     *
     * @param Package $package    The Adminer package
     */
    public function __construct(Package $package)
    {
        $this->package = $package;

        $jaxon = \jaxon();
        $this->trans = $jaxon->di()->get(Db\Translator::class);
        // Make the translator available into views
        $jaxon->view()->share('trans', $this->trans);
    }

    /**
     * Get a translated string
     * The first parameter is mandatory. Optional parameters can follow.
     *
     * @param string
     *
     * @return string
     */
    public function lang($idf)
    {
        return \call_user_func_array([$this->trans, "lang"], \func_get_args());
    }

    /**
     * Get the breadcrumbs items
     *
     * @return array
     */
    public function getBreadcrumbs()
    {
        return $this->breadcrumbs;
    }

    /**
     * Set the breadcrumbs items
     *
     * @param array $breadcrumbs
     *
     * @return void
     */
    protected function setBreadcrumbs(array $breadcrumbs)
    {
        $this->breadcrumbs = $breadcrumbs;
    }

    /**
     * Connect to a database server
     *
     * @param string $server    The selected server
     * @param string $database  The database name
     * @param string $schema    The database schema
     *
     * @return array
     */
    public function connect(string $server, string $database = '', string $schema = '')
    {
        // Prevent multiple calls.
        if (!$this->driver) {
            $di = \jaxon()->di();
            // Save the selected server options in the di container.
            $di->val('adminer_config_driver', $this->package->getServerDriver($server));
            $di->val('adminer_config_options', $this->package->getServerOptions($server));
            $this->driver = $di->get(DriverInterface::class);
            $this->util = $di->get(UtilInterface::class);
        }

        // Connect to the selected server
        $this->driver->connect($database, $schema);
    }
}
