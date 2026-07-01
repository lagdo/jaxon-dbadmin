<?php

namespace Lagdo\DbAdmin\Support\Driver\Proxy;

use Lagdo\DbAdmin\Support\Driver\AbstractDriverProxy;
use Lagdo\DbAdmin\Support\Driver\UiDto\DetailDto;

use function array_filter;
use function array_intersect;
use function array_keys;
use function array_key_exists;
use function array_map;
use function array_values;
use function explode;
use function in_array;
use function is_array;
use function is_string;
use function strtoupper;

/**
 * Proxy to server functions
 */
class ServerProxy extends AbstractDriverProxy
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
     * @param array $options    The server config options
     *
     * @return static
     */
    public function setOptions(array $options): static
    {
        // Set the user databases, if defined.
        if (is_array(($userDatabases = $options['access']['databases'] ?? null))) {
            $this->userDatabases = $userDatabases;
        }
        return $this;
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
        return $this->engine()->support($feature);
    }

    /**
     * Get the privilege list
     * This feature is available only for MySQL
     *
     * @param string $database  The database name
     *
     * @return array
     */
    public function getPrivileges(string $database = ''): array
    {
        $details = array_map(function(array $user) {
            // Fetch user grants
            $userDto = $this->engine()->getUserGrants($user["User"], $user["Host"]);
            return new DetailDto([
                'user' => $this->utils()->html($userDto->name),
                'host' => $this->utils()->html($userDto->host),
                'grants' => array_keys($userDto->grants),
            ]);
        }, $this->engine()->getUsers($database));

        return [
            'headers' => [
                $this->utils()->lang('Username'),
                $this->utils()->lang('Server'),
                '',
                '',
            ],
            'details' => $details,
        ];
    }

    /**
     * @param array $features
     * @param array $row
     *
     * @return void
     */
    private function makeFeatures(array &$features, array $row)
    {
        $contexts = explode(',', $row['Context']);
        foreach ($contexts as $context) {
            // Don't take 'Grant option' privileges.
            if ($row['Privilege'] === 'Grant option') {
                continue;
            }
            // Privileges of 'Server Admin' and 'File access on server' are merged
            if ($context === 'File access on server') {
                $context = 'Server Admin';
            }
            $privilege = $row['Privilege'];
            // Comment for this is 'No privileges - allow connect only'
            if ($context === 'Server Admin' && $privilege === 'Usage') {
                continue;
            }
            // MySQL bug #30305
            if ($context === 'Procedures' && $privilege === 'Create routine') {
                $context = 'Databases';
            }
            if (!isset($features[$context])) {
                $features[$context] = [];
            }
            $features[$context][$privilege] = $row['Comment'];
            if ($context === 'Tables' &&
                in_array($privilege, ['Select', 'Insert', 'Update', 'References'])) {
                $features['Columns'][$privilege] = $row['Comment'];
            }
        }
    }

    /**
     * @param string $privilege
     * @param string $desc
     * @param string $context
     * @param array $grants
     *
     * @return array
     */
    private function getPrivilegeInput(string $privilege, string $desc, string $context, array $grants): array
    {
        $htmlPrivilege = $this->utils()->html($privilege);
        $upperPrivilege = strtoupper($privilege);
        $htmlUpperPrivilege = $this->utils()->html($upperPrivilege);
        // echo '<tr><td' . ($desc ? ">$desc<td" : " colspan='2'") .
        //     ' lang="en" title="' . $this->utils()->html($comment) . '">' . $htmlPrivilege;
        $pos = 0;
        $nameBuilder = fn() => "'grants[$pos++][$htmlUpperPrivilege]'";
        $emptyChecker = fn($object) =>
            $context === 'Server Admin' && $object != (isset($grants['*.*']) ? '*.*' : '.*');
        $details = array_map(function($grant, $object) use($upperPrivilege, $emptyChecker, $nameBuilder) {
            $name = $nameBuilder();
            $value = $grant[$upperPrivilege] ?? false;
            return match(true) {
                $emptyChecker($object) => '',
                // isset($values['grant']) => "<select name=$name><option><option value='1'" .
                //     ($value ? ' selected' : '') . '>' . $this->utils()->lang('Grant') .
                //     "<option value='0'" . ($value == '0' ? ' selected' : '') . '>' .
                //     $this->utils()->lang('Revoke') . '</select>',
                default => "<input type='checkbox' name=$name" . ($value ? ' checked />' : ' />'),
            };
        }, $grants, array_keys($grants));

        return [$desc, $htmlPrivilege, ...$details];
    }

    /**
     * Get the user privileges
     *
     * @param array $grants     The user grants
     *
     * @return array
     */
    private function _getUserPrivileges(array $grants): array
    {
        // From user.inc.php
        $features = [
            '' => [
                'All privileges' => '',
            ],
            'Columns' => [],
        ];
        $rows = $this->engine()->rows('SHOW PRIVILEGES');
        foreach ($rows as $row) {
            $this->makeFeatures($features, $row);
        }

        foreach (array_keys($features['Tables']) as $privilege) {
            unset($features['Databases'][$privilege]);
        }

        $privileges = [];
        $contexts = [
            '' => '',
            'Server Admin' => $this->utils()->lang('Server'),
            'Databases' => $this->utils()->lang('Database'),
            'Tables' => $this->utils()->lang('Table'),
            'Columns' => $this->utils()->lang('Column'),
            'Procedures' => $this->utils()->lang('Routine'),
        ];
        foreach ($contexts as $context => $desc) {
            foreach ($features[$context] as $privilege => $comment) {
                $privileges[] = $this->getPrivilegeInput($privilege, $desc, $context, $grants);
            }
        }

        return $privileges;
    }

    /**
     * Get the grants of a user on a given host
     *
     * @return array
     */
    public function newUserPrivileges(): array
    {
        $grants = [".*" => []];

        $headers = [
            $this->utils()->lang('Contexts'),
            $this->utils()->lang('Privileges'),
        ];
        $pos = 0;
        foreach ($grants as $object => $grant) {
            $html = $this->utils()->html($object);
            //! separate db, table, columns, PROCEDURE|FUNCTION, routine
            $headers[] = $object === '*.*' ?
                "<input type=\"hidden\" name=\"objects[$pos]\" value=\"*.*\" />*.*" :
                "<input name=\"objects[$pos]\" value=\"$html\" autocapitalize=\"off\" />";
            $pos++;
        }

        return [
            'headers' => $headers,
            'user' => [
                'host' => [
                    'label' => $this->utils()->lang('Server'),
                    'value' => '',
                ],
                'name' => [
                    'label' => $this->utils()->lang('Username'),
                    'value' => '',
                ],
                'pass' => [
                    'label' => $this->utils()->lang('Password'),
                    'value' => '',
                ],
                'hashed' => [
                    'label' => $this->utils()->lang('Hashed'),
                    'value' => false,
                ],
            ],
            'details' => $this->_getUserPrivileges($grants),
        ];
    }

    /**
     * Get the grants of a user on a given host
     *
     * @param string $user      The username
     * @param string $host      The host name
     * @param string $database  The database name
     *
     * @return array
     */
    public function getUserPrivileges(string $user, string $host, string $database): array
    {
        $userDto = $this->engine()->getUserGrants($user, $host);
        if ($database !== '') {
            $userDto->grants = isset($userDto->grants[$database]) ?
                [$database => $userDto->grants[$database]] : [];
        }

        $headers = [
            $this->utils()->lang('Contexts'),
            $this->utils()->lang('Privileges'),
        ];
        $pos = 0;
        foreach ($userDto->grants as $object => $grant) {
            $html = $this->utils()->html($object);
            //! separate db, table, columns, PROCEDURE|FUNCTION, routine
            $headers[] = $object === '*.*' ?
                "<input type=\"hidden\" name=\"objects[$pos]\" value=\"*.*\" />*.*" :
                "<input name=\"objects[$pos]\" value=\"$html\" autocapitalize=\"off\" />";
            $pos++;
        }

        return [
            'headers' => $headers,
            'user' => [
                'host' => [
                    'label' => $this->utils()->lang('Server'),
                    'value' => $host,
                ],
                'name' => [
                    'label' => $this->utils()->lang('Username'),
                    'value' => $user,
                ],
                'pass' => [
                    'label' => $this->utils()->lang('Password'),
                    'value' => $userDto->password ,
                ],
                'hashed' => [
                    'label' => $this->utils()->lang('Hashed'),
                    'value' => ($userDto->password  !== ''),
                ],
            ],
            'details' => $this->_getUserPrivileges($userDto->grants),
        ];
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
            $this->finalDatabases = $this->engine()->databases(false);
            if (is_array($this->userDatabases)) {
                // Only keep databases that appear in the config.
                $this->finalDatabases = array_values(array_intersect($this->finalDatabases, $this->userDatabases));
            }
        }
        return $schemaAccess ? $this->finalDatabases : array_filter($this->finalDatabases,
            fn($database) => !$this->engine()->isSystemSchema($database));
    }

    /**
     * Get the connected database server details.
     *
     * @return array
     */
    public function getServerInfo(): array
    {
        return [
            'user' => $this->engine()->user(),
            'engine' => $this->engine()->name(),
            'version' => $this->engine()->serverInfo(),
            'extension' => $this->engine()->extension(),
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
        return $this->engine()->createDatabase($database, $collation);
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
        return $this->engine()->dropDatabase($database);
    }

    /**
     * Get the collation list
     *
     * @return array
     */
    public function getCollations(): array
    {
        return $this->engine()->collations();
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
        $tables = $this->engine()->countTables($databases);
        $collations = $this->engine()->collations();
        $details = [];
        foreach ($databases as $database) {
            $details[$database] = new DetailDto([
                'name' => $this->utils()->html($database),
                'collation' => $this->utils()->html($this->engine()->databaseCollation($database, $collations)),
                'tables' => array_key_exists($database, $tables) ? $tables[$database] : 0,
                'size' => $this->utils()->formatNumber($this->engine()->databaseSize($database)),
            ]);
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
        $processes = $this->engine()->processes();

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
                $attrs[] = is_string($val) ? $this->statement()->processAttr($process, $key, $val) : '(null)';
            }
            $details[] = new DetailDto($attrs);
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
        $variables = $this->engine()->variables();
        $details = [];
        // From variables.inc.php
        foreach ($variables as $key => $val) {
            $details[] = new DetailDto([
                $this->utils()->html($key),
                is_string($val) ? $this->utils()->str->shortenUtf8($val, 50) : '(null)',
            ]);
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
        $status = $this->engine()->statusVariables();
        $details = [];
        // From variables.inc.php
        foreach ($status as $key => $val) {
            $details[] = new DetailDto([
                $this->utils()->html($key),
                is_string($val) ? $this->utils()->html($val) : '(null)',
            ]);
        }

        return ['headers' => false, 'details' => $details];
    }
}
