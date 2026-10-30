<?php

namespace Lagdo\DbAdmin\Support\Provider\Config;

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
        $options = [
            'name' => $config->getOption("$prefix.name", ''),
            'driver' => $config->getOption("$prefix.driver", ''),
            'prefer_pdo' => $config->getOption("$prefix.prefer_pdo", false),
            'access' => $config->getOption("$prefix.access", []),
        ];
        if ($options['driver'] === 'sqlite') {
            if ($config->hasOption("$prefix.directory")) {
                $options['directory'] = $this->getOptionValue($prefix, 'directory');
            }
            return $options;
        }

        $port = (int)$this->getOptionValue($prefix, 'port', -1);
        return [
            ...$options,
            'host' => $this->getOptionValue($prefix, 'host'),
            ...($port < 0 ? [] : ['port' => $port]),
            ...$this->secret->with($config)->getCredentials($prefix),
        ];
    }
}
