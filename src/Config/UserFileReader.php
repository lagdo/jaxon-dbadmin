<?php

namespace Lagdo\DbAdmin\Config;

use Jaxon\Config\ConfigReader;
use Jaxon\Config\ConfigSetter;

use function array_filter;
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
     * Replace options with values from the .env config.
     *
     * @param array $values
     *
     * @return array
     */
    private function getOptionValues(array $values): array
    {
        if (!$this->useEnv) {
            return $values;
        }
        // The values in the server options are the names of the
        // corresponding options in the .env file.
        $values['servers'] = array_map(function(array $server) {
            foreach (['host', 'port', 'username', 'password'] as $option) {
                if (isset($server[$option])) {
                    $server[$option] = env($server[$option]);
                }
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
        unset($defaultOptions['provider']); // Remove the provider field.
        $setter = new ConfigSetter();
        $reader = new ConfigReader($setter);
        $userConfig = $setter->newConfig(['options' => $defaultOptions]);

        if (!is_file($this->configFilePath)) {
            return $userConfig->getOption('options');
        }

        $config = $reader->load($setter->newConfig(), $this->configFilePath);
        $commonOptions = $config->getOption('common', null);
        if (is_array($commonOptions)) {
            $userConfig = $setter->setOptions($userConfig, $commonOptions, 'options');
        }

        $fallbackOptions = $config->getOption('fallback', null);
        $userOptions = array_filter($config->getOption('users', []),
            fn($options) => $options['id'] ?? null === $this->auth->user());
        $userOptions = $userOptions[0] ?? $fallbackOptions;

        if (!is_array($userOptions)) {
            return $this->getOptionValues($userConfig->getOption('options'));
        }

        unset($userOptions['id']); // Remove the id field.
        $userConfig = $setter->setOptions($userConfig, $userOptions);

        return $this->getOptionValues($userConfig->getOption('options'));
    }
}
