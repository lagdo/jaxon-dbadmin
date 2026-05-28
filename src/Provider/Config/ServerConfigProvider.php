<?php

namespace Lagdo\DbAdmin\Support\Provider\Config;

class ServerConfigProvider
{
    use ConfigProviderTrait;

    /**
     * @param AccessConfigProvider $accessConfig
     */
    public function __construct(protected AccessConfigProvider $accessConfig)
    {}

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
     * @return array
     */
    public function readServerConfig(string $prefix): array
    {
        $config = $this->config();
        $options = [
            'name' => $config->getOption("$prefix.name", ''),
            'driver' => $config->getOption("$prefix.driver", ''),
        ];
        if ($options['driver'] === 'sqlite') {
            if ($config->hasOption("$prefix.directory")) {
                $options['directory'] = $this->getDirectory($prefix);
            }
            return $options;
        }

        return [
            ...$options,
            ...$this->accessConfig->with($config)->readAccessConfig($prefix),
        ];
    }
}
