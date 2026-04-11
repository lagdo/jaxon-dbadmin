<?php

namespace Lagdo\DbAdmin\Db\Driver\Proxy;

use Lagdo\DbAdmin\Db\Driver\AbstractProxy;

use function array_filter;
use function array_intersect;
use function array_key_exists;
use function array_keys;
use function array_values;
use function is_array;
use function is_string;

/**
 * Proxy to server functions
 */
class ServerProxy extends AbstractProxy
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
     * @param AbstractProxy $dbProxy
     * @param array $options    The server config options
     */
    public function __construct(AbstractProxy $dbProxy, array $options)
    {
        parent::__construct($dbProxy->driver(), $dbProxy->page(), $dbProxy->utils());
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
        return $this->driver()->support($feature);
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
            $this->finalDatabases = $this->driver()->databases(false);
            if (is_array($this->userDatabases)) {
                // Only keep databases that appear in the config.
                $this->finalDatabases = array_values(array_intersect($this->finalDatabases, $this->userDatabases));
            }
        }
        return $schemaAccess ? $this->finalDatabases : array_filter($this->finalDatabases,
            fn($database) => !$this->driver()->isSystemSchema($database));
    }

    /**
     * Connect to a database server
     *
     * @return array
     */
    public function getServerInfo(): array
    {
        return [
            'user' => $this->utils()->lang('Logged as: %s.',
                "<b>" . $this->utils()->html($this->driver()->user()) . "</b>"),
            'server' => $this->utils()->lang('%s version: %s.', $this->driver()->name(),
                "<b>" . $this->utils()->html($this->driver()->serverInfo()) . "</b>") . '<br/>' .
                $this->utils()->lang('PHP extension %s.',"<b>{$this->driver()->extension()}</b>"),
        ];
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
        return $this->driver()->createDatabase($database, $collation);
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
        return $this->driver()->dropDatabase($database);
    }

    /**
     * Get the collation list
     *
     * @return array
     */
    public function getCollations(): array
    {
        return $this->driver()->collations();
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
        // Get the database list
        $databases = $this->databases($schemaAccess);
        $tables = $this->driver()->countTables($databases);
        $collations = $this->driver()->collations();
        $details = [];
        foreach ($databases as $database) {
            $details[] = [
                'name' => $this->utils()->html($database),
                'collation' => $this->utils()->html($this->driver()->databaseCollation($database, $collations)),
                'tables' => array_key_exists($database, $tables) ? $tables[$database] : 0,
                'size' => $this->utils()->trans->formatNumber($this->driver()->databaseSize($database)),
            ];
        }

        return [
            'headers' => [
                $this->utils()->lang('Database'),
                $this->utils()->lang('Collation'),
                $this->utils()->lang('Tables'),
                $this->utils()->lang('Size'),
                '',
            ],
            'databases' => $databases,
            'details' => $details,
        ];
    }

    /**
     * Get the processes
     *
     * @return array
     */
    public function getProcesses(): array
    {
        // From processlist.inc.php
        $processes = $this->driver()->processes();

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
                $attrs[] = is_string($val) ? $this->grammar()->processAttr($process, $key, $val) : '(null)';
            }
            $details[] = $attrs;
        }

        return ['headers' => $headers, 'details' => $details];
    }

    /**
     * Get the variables
     *
     * @return array
     */
    public function getVariables(): array
    {
        // From variables.inc.php
        $variables = $this->driver()->variables();
        $details = [];
        // From variables.inc.php
        foreach ($variables as $key => $val) {
            $details[] = [$this->utils()->html($key), is_string($val) ? $this->utils()->str->shortenUtf8($val, 50) : '(null)'];
        }

        return ['headers' => false, 'details' => $details];
    }

    /**
     * Get the server status
     *
     * @return array
     */
    public function getStatus(): array
    {
        // From variables.inc.php
        $status = $this->driver()->statusVariables();
        $details = [];
        // From variables.inc.php
        foreach ($status as $key => $val) {
            $details[] = [$this->utils()->html($key), is_string($val) ? $this->utils()->html($val) : '(null)'];
        }

        return ['headers' => false, 'details' => $details];
    }
}
