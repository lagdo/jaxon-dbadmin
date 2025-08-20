<?php

namespace Lagdo\DbAdmin\Config;

use Jaxon\Config\ConfigReader;
use Jaxon\Config\ConfigSetter;

use function array_map;
use function array_filter;
use function count;
use function env;
use function is_array;
use function is_file;

class UserFileReader
{
    /**
     * The constructor
     *
     * @param AuthInterface $auth
     * @param string $configFilePath
     * @param bool $useEnv
     */
    public function __construct(private AuthInterface $auth,
        private string $configFilePath, private bool $useEnv = false)
    {}

    /**
     * @param array $server
     *
     * @return bool
     */
    private function checkServer(array $server): bool
    {
        if (!isset($server['name']) || !isset($server['driver'])) {
            return false;
        }
        if ($server['driver'] === 'sqlite') {
            return isset($server['directory']) && count($server) === 3;
        }
        return isset($server['host']) && isset($server['port']) &&
            isset($server['username']) && isset($server['password']) &&
            count($server) === 6;
    }

    /**
     * Replace options with values from the .env config.
     *
     * @param array $values
     *
     * @return array
     */
    private function getOptionValues(array $values): array
    {
        // Filter the servers list on valid entries
        $values['servers'] = array_filter($values['servers'],
            fn(array $server) => $this->checkServer($server));

        if (!$this->useEnv) {
            return $values;
        }

        // The values in the server options are the names of the
        // corresponding options in the .env file.
        $options = ['host', 'port', 'username', 'password'];
        $values['servers'] = array_map(function(array $server) use($options) {
            foreach ($options as $option) {
                $server[$option] = env($server[$option]);
            }
            return $server;
        }, $values['servers'] ?? []);
        return $values;
    }

    /**
     * Get the options for the authenticated user.
     *
     * @param array $defaultOptions
     *
     * @return array
     */
    public function getOptions(array $defaultOptions): array
    {
        // If the config file doesn't exists, return an empty array.
        if (!is_file($this->configFilePath)) {
            return [];
        }

        // The key to use for the user options
        $userKey = 'user';
        // Remove the provider field.
        unset($defaultOptions['provider']);

        $setter = new ConfigSetter();
        $reader = new ConfigReader($setter);
        $userConfig = $setter->newConfig([$userKey => $defaultOptions]);

        $config = $reader->load($setter->newConfig(), $this->configFilePath);
        $commonOptions = $config->getOption('common', null);
        if (is_array($commonOptions)) {
            $userConfig = $setter->setOptions($userConfig, $commonOptions, $userKey);
        }

        $fallbackOptions = $config->getOption('fallback', null);
        $userOptions = array_filter($config->getOption('users', []),
            fn($options) => $options['id'] ?? null === $this->auth->user());
        $userOptions = $userOptions[0] ?? $fallbackOptions;

        if (!is_array($userOptions)) {
            return $this->getOptionValues($userConfig->getOption($userKey));
        }

        unset($userOptions['id']); // Remove the id field.
        $userConfig = $setter->setOptions($userConfig, $userOptions, $userKey);

        return $this->getOptionValues($userConfig->getOption($userKey));
    }
}
