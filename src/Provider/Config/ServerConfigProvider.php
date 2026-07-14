<?php

namespace Lagdo\DbAdmin\Support\Provider\Config;

use Lagdo\DbAdmin\Support\Provider\Secret\SecretConfigProvider;

class ServerConfigProvider
{
    use ConfigProviderTrait;

    /**
     * @param SecretConfigProvider $secret
     */
    public function __construct(protected SecretConfigProvider $secret)
    {}

    /**
     * @param string $prefix
     *
     * @return array
     */
    public function readServerConfig(string $prefix): array
    {
        $config = $this->config();
        $driver = $config->getOption("$prefix.driver", '');
        if ($driver === 'sqlite') {
            return !$config->hasOption("$prefix.directory") ? [
                'name' => $config->getOption("$prefix.name", ''),
                'driver' => $driver,
                'access' => $config->getOption("$prefix.access", []),
            ] : [
                'name' => $config->getOption("$prefix.name", ''),
                'driver' => $driver,
                'directory' => $this->getOptionValue($prefix, 'directory'),
                'access' => $config->getOption("$prefix.access", []),
            ];
        }

        $port = (int)$this->getOptionValue($prefix, 'port', -1);
        return [
            'name' => $config->getOption("$prefix.name", ''),
            'driver' => $driver,
            'host' => $this->getOptionValue($prefix, 'host'),
            ...($port < 0 ? [] : ['port' => $port]),
            ...$this->secret->with($config)->getCredentials($prefix),
            'access' => $config->getOption("$prefix.access", []),
        ];
    }
}
