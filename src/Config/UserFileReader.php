<?php

namespace Lagdo\DbAdmin\Config;

use Jaxon\Config\ConfigReader;
use Jaxon\Config\ConfigSetter;

use function array_map;
use function array_filter;
use function array_values;
use function count;
use function env;
use function in_array;
use function is_array;
use function is_file;
use function is_string;

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
     * @param array $options
     *
     * @return bool
     */
    private function checkUser(array $options): bool
    {
        $user = $options['id']['user'] ?? null;
        return is_string($user) && $this->auth->user() === $user;
    }

    /**
     * @param array $options
     *
     * @return bool
     */
    private function checkUsers(array $options): bool
    {
        $users = $options['id']['users'] ?? null;
        return is_array($users) && in_array($this->auth->user(), $users);
    }

    /**
     * @param array $options
     *
     * @return bool
     */
    private function checkRole(array $options): bool
    {
        $role = $options['id']['role'] ?? null;
        return is_string($role) && $this->auth->role() === $role;
    }

    /**
     * @param array $options
     *
     * @return bool
     */
    private function checkRoles(array $options): bool
    {
        $roles = $options['id']['roles'] ?? null;
        return is_array($roles) && in_array($this->auth->role(), $roles);
    }

    /**
     * @param array $options
     *
     * @return bool
     */
    private function userMatches(array $options): bool
    {
        return $this->checkUser($options) || $this->checkUsers($options) ||
            $this->checkRole($options) || $this->checkRoles($options);
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
        $values['servers'] = array_filter($values['servers'] ?? [],
            fn(array $server) => $this->checkServer($server));
        if (!$this->useEnv) {
            return $values;
        }

        // The values in the server options are the names of the
        // corresponding options in the .env file.
        $options = ['host', 'port', 'username', 'password'];
        $values['servers'] = array_map(function(array $server) use($options) {
            if ($server['driver'] !== 'sqlite') {
                foreach ($options as $option) {
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

        $userList = $config->getOption('users', []);
        $userList = array_values(array_filter($userList,
            fn($options) => $this->userMatches($options)));
        $userOptions = $userList[0] ?? $fallbackOptions;

        if (!is_array($userOptions)) {
            return $this->getOptionValues($userConfig->getOption($userKey));
        }

        unset($userOptions['id']); // Remove the id field.
        $userConfig = $setter->setOptions($userConfig, $userOptions, $userKey);

        return $this->getOptionValues($userConfig->getOption($userKey));
    }
}
