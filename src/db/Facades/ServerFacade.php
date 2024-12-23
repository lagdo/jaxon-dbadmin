<?php

namespace Lagdo\DbAdmin\Db\Facades;

use function array_key_exists;
use function is_array;
use function array_intersect;
use function array_values;
use function compact;
use function is_string;
use function array_keys;

/**
 * Facade to server functions
 */
class ServerFacade extends AbstractFacade
{
    /**
     * The final database list
     *
     * @var array|null
     */
    protected $finalDatabases = null;

    /**
     * The databases the user has access to
     *
     * @var array|null
     */
    protected $userDatabases = null;

    /**
     * The constructor
     *
     * @param AbstractFacade $dbFacade
     * @param array $options    The server config options
     */
    public function __construct(AbstractFacade $dbFacade, array $options)
    {
        parent::__construct($dbFacade);
        // Set the user databases, if defined.
        if (is_array(($userDatabases = $options['access']['databases'] ?? null))) {
            $this->userDatabases = $userDatabases;
        }
    }

    /**
     * Get the databases from the connected server
     *
     * @return array
     */
    protected function databases(): array
    {
        if ($this->finalDatabases === null) {
            // Get the database lists
            // Passing false as parameter to this call prevent from using the slow_query() function,
            // which outputs data to the browser are prepended to the Jaxon response.
            $this->finalDatabases = $this->driver->databases(false);
            if (is_array($this->userDatabases)) {
                // Only keep databases that appear in the config.
                $this->finalDatabases = array_values(array_intersect($this->finalDatabases, $this->userDatabases));
            }
        }
        return $this->finalDatabases;
    }

    /**
     * Connect to a database server
     *
     * @return array
     */
    public function getServerInfo(): array
    {
        $server = $this->trans->lang(
            '%s version: %s. PHP extension %s.',
            $this->driver->name(),
            "<b>" . $this->admin->html($this->driver->serverInfo()) . "</b>",
            "<b>{$this->driver->extension()}</b>"
        );
        $user = $this->trans->lang('Logged as: %s.', "<b>" . $this->admin->html($this->driver->user()) . "</b>");

        return compact('server', 'user');
    }

    /**
     * Create a database
     *
     * @param string $database  The database name
     * @param string $collation The database collation
     *
     * @return bool
     */
    public function createDatabase(string $database, string $collation = ''): bool
    {
        return $this->driver->createDatabase($database, $collation);
    }

    /**
     * Drop a database
     *
     * @param string $database  The database name
     *
     * @return bool
     */
    public function dropDatabase(string $database): bool
    {
        return $this->driver->dropDatabase($database);
    }

    /**
     * Get the collation list
     *
     * @return array
     */
    public function getCollations(): array
    {
        return $this->driver->collations();
    }

    /**
     * Get the database list
     *
     * @return array
     */
    public function getDatabases(): array
    {
        $headers = [
            $this->trans->lang('Database'),
            $this->trans->lang('Collation'),
            $this->trans->lang('Tables'),
            $this->trans->lang('Size'),
            '',
        ];

        // Get the database list
        $databases = $this->databases();
        $tables = $this->driver->countTables($databases);
        $collations = $this->driver->collations();
        $details = [];
        foreach ($databases as $database) {
            $details[] = [
                'name' => $this->admin->html($database),
                'collation' => $this->admin->html($this->driver->databaseCollation($database, $collations)),
                'tables' => array_key_exists($database, $tables) ? $tables[$database] : 0,
                'size' => $this->trans->formatNumber($this->driver->databaseSize($database)),
            ];
        }

        return compact('headers', 'databases', 'details');
    }

    /**
     * Get the processes
     *
     * @return array
     */
    public function getProcesses(): array
    {
        // From processlist.inc.php
        $processes = $this->driver->processes();

        // From processlist.inc.php
        // TODO: Add a kill column in the headers
        $headers = [];
        $details = [];
        if (($process = reset($processes)) !== false) {
            // Set the keys of the first entry as headers
            $headers = array_keys($process);
        }
        foreach ($processes as $process) {
            $attrs = [];
            foreach ($process as $key => $val) {
                $attrs[] = is_string($val) ? $this->driver->processAttr($process, $key, $val) : '(null)';
            }
            $details[] = $attrs;
        }
        return compact('headers', 'details');
    }

    /**
     * Get the variables
     *
     * @return array
     */
    public function getVariables(): array
    {
        // From variables.inc.php
        $variables = $this->driver->variables();

        $headers = false;

        $details = [];
        // From variables.inc.php
        foreach ($variables as $key => $val) {
            $details[] = [$this->admin->html($key), is_string($val) ? $this->admin->shortenUtf8($val, 50) : '(null)'];
        }

        return compact('headers', 'details');
    }

    /**
     * Get the server status
     *
     * @return array
     */
    public function getStatus(): array
    {
        // From variables.inc.php
        $status = $this->driver->statusVariables();

        $headers = false;
        $details = [];
        // From variables.inc.php
        foreach ($status as $key => $val) {
            $details[] = [$this->admin->html($key), is_string($val) ? $this->admin->html($val) : '(null)'];
        }

        return compact('headers', 'details');
    }
}
