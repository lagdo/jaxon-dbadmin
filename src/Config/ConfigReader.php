<?php

namespace Lagdo\DbAdmin\Db\Config;

use Jaxon\Config\Config;

use function is_string;
use function preg_match;

class ConfigReader
{
    /**
     * @var string
     */
    private string $captureRegex = '/^env\((.*)\)$/';

    /**
     * @var Config $config
     */
    private Config $config;

    /**
     * Get the value of a given package option
     *
     * @param string $option    The option name
     * @param mixed $default    The default value
     *
     * @return mixed
     */
    final protected function getOption(string $option, $default = null): mixed
    {
        return $this->config->getOption($option, $default);
    }

    /**
     * @param string $prefix
     * @param string $option
     *
     * @return mixed
     */
    protected function getOptionValue(string $prefix, string $option): mixed
    {
        $value = $this->getOption("$prefix.{$option}");
        // We need to capture the matching string.
        $match = preg_match($this->captureRegex, $value, $matches);
        return $match === false || !isset($matches[1]) ? $value : env($matches[1]);
    }

    /**
     * @param string $prefix
     *
     * @return bool
     */
    protected function hasDirectory(string $prefix): bool
    {
        return $this->config->hasOption("$prefix.directory");
    }

    /**
     * @param string $prefix
     *
     * @return string
     */
    protected function getDirectory(string $prefix): string
    {
        return $this->getOptionValue($prefix, 'directory');
    }

    /**
     * @param string $prefix
     *
     * @return string
     */
    protected function getHost(string $prefix): string
    {
        return $this->getOptionValue($prefix, 'host');
    }

    /**
     * @param string $prefix
     *
     * @return int
     */
    protected function getPort(string $prefix): int
    {
        return (int)$this->getOptionValue($prefix, 'port');
    }

    /**
     * @param string $prefix
     *
     * @return string
     */
    protected function getUsername(string $prefix): string
    {
        return $this->getOptionValue($prefix, 'username');
    }

    /**
     * @param string $prefix
     *
     * @return string
     */
    protected function getPassword(string $prefix): string
    {
        return $this->getOptionValue($prefix, 'password');
    }

    /**
     * @param Config $config
     * @param string $prefix
     *
     * @return array
     */
    public function readServerConfig(Config $config, string $prefix): array
    {
        // Save the current config locally.
        $this->config = $config;

        $options = [
            'name' => $this->getOption("$prefix.name"),
            'driver' => $this->getOption("$prefix.driver"),
        ];
        if ($options['driver'] === 'sqlite' &&
            $this->config->hasOption("$prefix.directory")) {
            $options['directory'] = $this->getDirectory($prefix);
            return $options;
        }

        if(($host = $this->getHost($prefix)) !== '') {
            $options['host'] = $host;
        }
        if ($this->config->hasOption("$prefix.port")) {
            $options['port'] = $this->getPort($prefix);
        }
        if(($username = $this->getUsername($prefix)) !== '') {
            $options['username'] = $username;
        }
        if(($password = $this->getPassword($prefix)) !== '') {
            $options['password'] = $password;
        }
        return $options;
    }
}
