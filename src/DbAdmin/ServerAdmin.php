<?php

namespace Lagdo\DbAdmin\DbAdmin;

use Exception;

/**
 * Admin server functions
 */
class ServerAdmin extends AbstractAdmin
{
    /**
     * The final database list
     *
     * @var array
     */
    protected $finalDatabases = null;

    /**
     * The databases the user has access to
     *
     * @var array
     */
    protected $userDatabases = null;

    /**
     * The constructor
     *
     * @param array $options    The server config options
     */
    public function __construct(array $options)
    {
        // Set the user databases, if defined.
        if (\array_key_exists('access', $options) &&
            \is_array($options['access']) &&
            \array_key_exists('databases', $options['access']) &&
            \is_array($options['access']['databases'])) {
            $this->userDatabases = $options['access']['databases'];
        }
    }

    /**
     * Get the databases from the connected server
     *
     * @return array
     */
    protected function databases()
    {
        // Get the database lists
        // Passing false as parameter to this call prevent from using the slow_query() function,
        // which outputs data to the browser are prepended to the Jaxon response.
        if ($this->finalDatabases === null) {
            $this->finalDatabases = $this->driver->databases(false);
            if (\is_array($this->userDatabases)) {
                // Only keep databases that appear in the config.
                $this->finalDatabases = \array_intersect($this->finalDatabases, $this->userDatabases);
                $this->finalDatabases = \array_values($this->finalDatabases);
            }
        }
        return $this->finalDatabases;
    }

    /**
     * Connect to a database server
     *
     * @return void
     */
    public function getServerInfo()
    {
        $server = $this->trans->lang(
            '%s version: %s. PHP extension %s.',
            $this->driver->name(),
            "<b>" . $this->util->html($this->driver->serverInfo()) . "</b>",
            "<b>{$this->driver->extension()}</b>"
        );
        $user = $this->trans->lang('Logged as: %s.', "<b>" . $this->util->html($this->driver->user()) . "</b>");

        $sqlActions = [
            'server-command' => $this->trans->lang('SQL command'),
            'server-import' => $this->trans->lang('Import'),
            'server-export' => $this->trans->lang('Export'),
        ];

        // Content from the connect_error() function in connect.inc.php
        $menuActions = [
            'databases' => $this->trans->lang('Databases'),
        ];
        // if($this->driver->support('database'))
        // {
        //     $menuActions['databases'] = $this->trans->lang('Databases');
        // }
        if ($this->driver->support('privileges')) {
            $menuActions['privileges'] = $this->trans->lang('Privileges');
        }
        if ($this->driver->support('processlist')) {
            $menuActions['processes'] = $this->trans->lang('Process list');
        }
        if ($this->driver->support('variables')) {
            $menuActions['variables'] = $this->trans->lang('Variables');
        }
        if ($this->driver->support('status')) {
            $menuActions['status'] = $this->trans->lang('Status');
        }

        return \compact('server', 'user', 'sqlActions', 'menuActions');
    }

    /**
     * Create a database
     *
     * @param string $database  The database name
     * @param string $collation The database collation
     *
     * @return bool
     */
    public function createDatabase(string $database, string $collation = '')
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
    public function dropDatabase(string $database)
    {
        return $this->driver->dropDatabases([$database]);
    }

    /**
     * Get the collation list
     *
     * @return array
     */
    public function getCollations()
    {
        return $this->driver->collations();
    }

    /**
     * Get the database list
     *
     * @return array
     */
    public function getDatabases()
    {
        $mainActions = [
            'add-database' => $this->trans->lang('Create database'),
        ];

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
                'name' => $this->util->html($database),
                'collation' => $this->util->html($this->driver->databaseCollation($database, $collations)),
                'tables' => \array_key_exists($database, $tables) ? $tables[$database] : 0,
                'size' => $this->trans->formatNumber($this->driver->databaseSize($database)),
            ];
        }

        return \compact('headers', 'databases', 'details', 'mainActions');
    }

    /**
     * Get the processes
     *
     * @return array
     */
    public function getProcesses()
    {
        // From processlist.inc.php
        $processes = $this->driver->processes();

        $jush = $this->driver->jush();
        // From processlist.inc.php
        // TODO: Add a kill column in the headers
        $headers = [];
        $details = [];
        foreach ($processes as $process) {
            // Set the keys of the first etry as headers
            if (\count($headers) === 0) {
                $headers = \array_keys($process);
            }
            $detail = [];
            foreach ($process as $key => $val) {
                $match = \array_key_exists('Command', $process) &&
                    \preg_match("~Query|Killed~", $process["Command"]);
                $detail[] =
                    ($jush == "sql" && $key == "Info" && $match && $val != "") ||
                    ($jush == "pgsql" && $key == "current_query" && $val != "<IDLE>") ||
                    ($jush == "oracle" && $key == "sql_text" && $val != "") ?
                    "<code class='jush-{$jush}'>" . $this->util->shortenUtf8($val, 50) .
                    "</code>" . $this->trans->lang('Clone') : $this->util->html($val);
            }
            $details[] = $detail;
        }

        return \compact('headers', 'details');
    }

    /**
     * Get the variables
     *
     * @return array
     */
    public function getVariables()
    {
        // From variables.inc.php
        $variables = $this->driver->variables();

        $headers = false;

        $details = [];
        // From variables.inc.php
        foreach ($variables as $key => $val) {
            $details[] = [$this->util->html($key), $this->util->shortenUtf8($val, 50)];
        }

        return \compact('headers', 'details');
    }

    /**
     * Get the server status
     *
     * @return array|null
     */
    public function getStatus()
    {
        // From variables.inc.php
        $status = $this->driver->statusVariables();
        if (!\is_array($status)) {
            $status = [];
        }

        $headers = false;

        $details = [];
        // From variables.inc.php
        foreach ($status as $key => $val) {
            $details[] = [$this->util->html($key), $this->util->html($val)];
        }

        return \compact('headers', 'details');
    }
}
