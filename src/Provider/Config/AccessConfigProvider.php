<?php

namespace Lagdo\DbAdmin\Support\Provider\Config;

class AccessConfigProvider
{
    use ConfigProviderTrait;

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
     * @param string $prefix
     *
     * @return array
     */
    public function readAccessConfig(string $prefix): array
    {
        $options = [];
        if(($host = $this->getHost($prefix)) !== '') {
            $options['host'] = $host;
        }
        if ($this->config()->hasOption("$prefix.port")) {
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
