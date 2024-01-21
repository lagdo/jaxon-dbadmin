<?php

namespace Lagdo\DbAdmin;

use Lagdo\DbAdmin\App\Package;
use Lagdo\DbAdmin\Db\Admin;
use Lagdo\DbAdmin\DbAdmin\AbstractAdmin;
use Lagdo\DbAdmin\Driver\DriverInterface;
use Lagdo\DbAdmin\Driver\UtilInterface;
use Lagdo\DbAdmin\Db\Translator;
use Jaxon\Di\Container;

use function call_user_func_array;
use function func_get_args;

/**
 * Admin to calls to the database functions
 */
class DbAdmin extends AbstractAdmin
{
    use DbAdmin\Server\ServerTrait;
    use DbAdmin\User\UserTrait;
    use DbAdmin\Database\DatabaseTrait;
    use DbAdmin\Table\TableTrait;
    use DbAdmin\Select\SelectTrait;
    use DbAdmin\Query\QueryTrait;
    use DbAdmin\View\ViewTrait;
    use DbAdmin\Command\CommandTrait;
    use DbAdmin\Export\ExportTrait;
    use DbAdmin\Import\ImportTrait;

    /**
     * The breadcrumbs items
     *
     * @var array
     */
    protected $breadcrumbs = [];

    /**
     * @var Container
     */
    protected $di;

    /**
     * The Jaxon DbAdmin package
     *
     * @var Package
     */
    protected $package;

    /**
     * The constructor
     *
     * @param Package $package    The DbAdmin package
     */
    public function __construct(Container $di, Package $package, Translator $trans)
    {
        $this->di = $di;
        $this->package = $package;
        $this->trans = $trans;
        // Make the translator available into views
        $this->package->view()->share('trans', $this->trans);
    }

    /**
     * Get a translated string
     * The first parameter is mandatory. Optional parameters can follow.
     *
     * @param string
     *
     * @return string
     */
    public function lang($idf): string
    {
        return call_user_func_array([$this->trans, "lang"], func_get_args());
    }

    /**
     * Get the breadcrumbs items
     *
     * @return array
     */
    public function getBreadcrumbs(): array
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
     * @return AbstractAdmin
     */
    public function admin(): AbstractAdmin
    {
        return $this;
    }

    /**
     * Connect to a database server
     *
     * @param string $server    The selected server
     * @param string $database  The database name
     * @param string $schema    The database schema
     *
     * @return void
     */
    public function connect(string $server, string $database = '', string $schema = '')
    {
        // Prevent multiple calls.
        if (!$this->driver) {
            // Save the selected server options in the di container.
            $this->di->val('dbadmin_config_driver', $this->package->getServerDriver($server));
            $this->di->val('dbadmin_config_options', $this->package->getServerOptions($server));
            $this->driver = $this->di->get(DriverInterface::class);
            $this->util = $this->di->get(UtilInterface::class);
            $this->admin = $this->di->get(Admin::class);
        }
        // Connect to the selected server
        $this->driver->connect($database, $schema);
    }

    /**
     * Get the remembered queries
     *
     * @return array
     */
    public function queries(): array
    {
        return $this->driver->queries();
    }
}
