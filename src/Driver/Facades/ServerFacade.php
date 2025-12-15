<?php

namespace Lagdo\DbAdmin\Db\Driver\Facades;

use function array_filter;
use function array_intersect;
use function array_key_exists;
use function array_keys;
use function array_values;
use function compact;
use function is_array;
use function is_string;

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
     * Check if a feature is supported
     *
     * @param string $feature
     *
     * @return bool
     */
    public function support(string $feature): bool
    {
        return $this->driver->support($feature);
    }

    /**
     * Get the databases from the connected server
     *
     * @param bool $schemaAccess
     *
     * @return array
     */
    protected function databases(bool $schemaAccess): array
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
        return $schemaAccess ? $this->finalDatabases : array_filter($this->finalDatabases,
            fn($database) => !$this->driver->isSystemSchema($database));
    }

    /**
     * Connect to a database server
     *
     * @return array
     */
    public function getServerInfo(): array
    {
        $server = $this->utils->trans->lang(
            '%s version: %s. PHP extension %s.',
            $this->driver->name(),
            "<b>" . $this->utils->str->html($this->driver->serverInfo()) . "</b>",
            "<b>{$this->driver->extension()}</b>"
        );
        $user = $this->utils->trans->lang('Logged as: %s.', "<b>" . $this->utils->str->html($this->driver->user()) . "</b>");

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
     * @param bool $schemaAccess
     *
     * @return array
     */
    public function getDatabases(bool $schemaAccess): array
    {
        $headers = [
            $this->utils->trans->lang('Database'),
            $this->utils->trans->lang('Collation'),
            $this->utils->trans->lang('Tables'),
            $this->utils->trans->lang('Size'),
            '',
        ];

        // Get the database list
        $databases = $this->databases($schemaAccess);
        $tables = $this->driver->countTables($databases);
        $collations = $this->driver->collations();
        $details = [];
        foreach ($databases as $database) {
            $details[] = [
                'name' => $this->utils->str->html($database),
                'collation' => $this->utils->str->html($this->driver->databaseCollation($database, $collations)),
                'tables' => array_key_exists($database, $tables) ? $tables[$database] : 0,
                'size' => $this->utils->trans->formatNumber($this->driver->databaseSize($database)),
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
            $details[] = [$this->utils->str->html($key), is_string($val) ? $this->utils->str->shortenUtf8($val, 50) : '(null)'];
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
            $details[] = [$this->utils->str->html($key), is_string($val) ? $this->utils->str->html($val) : '(null)'];
        }

        return compact('headers', 'details');
    }
}
