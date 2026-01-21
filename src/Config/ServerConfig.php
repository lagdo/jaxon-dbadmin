<?php

namespace Lagdo\DbAdmin\Db\Config;

use Jaxon\Config\Config;

use function array_filter;
use function array_map;
use function count;
use function in_array;
use function is_numeric;
use function is_string;
use function preg_match;

class ServerConfig
{
    /**
     * @var array
     */
    public const DRIVERS = ['pgsql', 'mysql', 'sqlite'];

    /**
     * @var string
     */
    private string $compareRegex = '/^env\(.*\)$/';

    /**
     * @var array<Config>
     */
    private array $configs = [];

    /**
     * The constructor
     *
     * @param Config $config
     * @param ConfigReader $reader
     */
    public function __construct(protected readonly Config $config, private ConfigReader $reader)
    {}

    /**
     * Get the value of a given package option
     *
     * @param string $option    The option name
     * @param mixed $default    The default value
     *
     * @return mixed
     */
    public function getOption(string $option, $default = null): mixed
    {
        return $this->config->getOption($option, $default);
    }

    /**
     * Get the name of a given server
     *
     * @param string $server    The server name in the configuration
     *
     * @return string
     */
    public function getServerName(string $server): string
    {
        return $this->getOption("servers.$server.name", '');
    }

    /**
     * Get the driver of a given server
     *
     * @param string $server    The server name in the configuration
     *
     * @return string
     */
    public function getServerDriver(string $server): string
    {
        return $this->getOption("servers.$server.driver", '');
    }

    /**
     * Check if the user has access to a server
     *
     * @param string $server      The database server
     *
     * return bool
     */
    public function getServerAccess(string $server): bool
    {
        // Check in server options
        $serverAccess = $this->getOption("servers.$server.access.server", null);
        $globalAccess = $this->getOption('access.server', true);
        return match(true) {
            $serverAccess === true,
            $serverAccess === false => $serverAccess,
            // Check in global options
            default => $globalAccess === true,
        };
    }

    /**
     * @param string $value
     *
     * @return bool
     */
    private function isEnvVar(string $value): bool
    {
        return preg_match($this->compareRegex, $value) !== false;
    }

    /**
     * @param string $prefix
     *
     * @return bool
     */
    final public function checkPortNumber(string $prefix): bool
    {
        $port = $this->getOption("$prefix.port");
        return match(true) {
            !$this->config->hasOption("$prefix.port"),
            is_numeric($port) => true,
            !is_string($port) => false,
            // The port number can also be defined with an env var.
            default => $this->isEnvVar($port)
        };
    }

    /**
     * @param string $prefix
     *
     * @return bool
     */
    private function hasDbServer(string $prefix): bool
    {
        $name = $this->getOption("$prefix.name");
        $driver = $this->getOption("$prefix.driver");
        return $this->config->hasOption("$prefix.name") &&
            $this->config->hasOption("$prefix.driver") &&
            is_string($name) && is_string($driver) &&
            in_array($driver, self::DRIVERS) &&
            $this->checkPortNumber($prefix);
    }

    /**
     * @param array $options
     *
     * @return bool
     */
    private function checkOptions(array $options): bool
    {
        return $options['driver'] === 'sqlite' ?
            // Options for the SQLite database.
            isset($options['directory']) && is_string($options['directory']) :
            // Options for a server database.
            isset($options['username']) && isset($options['password']) &&
                isset($options['host']) && is_string($options['username']) &&
                is_string($options['password']) && is_string($options['host']);
    }

    /**
     * @param string $prefix
     *
     * @return array
     */
    private function _readConfig(string $prefix): array
    {
        if (!$this->hasDbServer($prefix)) {
            return [];
        }

        $options = $this->reader->readServerConfig($this->config, $prefix);
        return $this->checkOptions($options) ? $options : [];
    }

    /**
     * @param string $prefix
     *
     * @return array
     */
    private function readConfig(string $prefix): array
    {
        return $this->configs[$prefix] ??= $this->_readConfig($prefix);
    }

    /**
     * Get the database servers
     *
     * @return array
     */
    public function getServers(): array
    {
        return array_filter(array_map($this->readConfig(...),
            $this->config->getOptionNames('servers')),
            fn(array $config) => count($config) > 0);
    }

    /**
     * @param string $server
     *
     * @return array
     */
    public function getServerConfig(string $server): array
    {
        return $this->readConfig("servers.$server");
    }

    /**
     * @return bool
     */
    public function hasAuditDatabase(): bool
    {
        return $this->hasDbServer('audit.database');
    }

    /**
     * @return array|null
     */
    public function getAuditDatabase(): array|null
    {
        return $this->hasAuditDatabase() ? $this->readConfig('audit.database') : null;
    }

    /**
     * @return array
     */
    public function getAuditOptions(): array
    {
        return $this->getOption('audit.options', []);
    }
}
