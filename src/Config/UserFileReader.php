<?php

namespace Lagdo\DbAdmin\Db\Config;

use Jaxon\Config\ConfigReader;
use Jaxon\Config\ConfigSetter;

use function array_map;
use function array_filter;
use function array_values;
use function env;
use function in_array;
use function is_array;
use function is_file;
use function is_numeric;
use function is_string;
use function preg_match;

class UserFileReader
{
    /**
     * @var string
     */
    private string $compareRegex = '/^env\(.*\)$/';

    /**
     * @var string
     */
    private string $captureRegex = '/^env\((.*)\)$/';

    /**
     * The constructor
     *
     * @param AuthInterface $auth
     */
    public function __construct(private AuthInterface $auth)
    {}

    /**
     * @param string $value
     *
     * @return bool
     */
    private function callsEnvVar(string $value): bool
    {
        return preg_match($this->compareRegex, $value) !== false;
    }

    /**
     * @param array $server
     *
     * @return bool
     */
    private function checkPortNumber(array $server): bool
    {
        if (!isset($server['port']) || is_numeric($server['port'])) {
            return true;
        }
        if (!is_string($server['port'])) {
            return false;
        }
        return $this->callsEnvVar($server['port']);
    }

    /**
     * @param array $server
     *
     * @return bool
     */
    private function checkServer(array $server): bool
    {
        if (!isset($server['name']) || !isset($server['driver']) ||
            !is_string($server['name']) || !is_string($server['driver'])) {
            return false;
        }
        if ($server['driver'] === 'sqlite') {
            return isset($server['directory']) && is_string($server['directory']);
        }
        return isset($server['username']) && isset($server['password']) &&
            isset($server['host']) && is_string($server['username']) &&
            is_string($server['password']) && is_string($server['host']) &&
            $this->checkPortNumber($server);
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
     * @param string $value
     *
     * @return mixed
     */
    private function getOptionValue(string $value): mixed
    {
        // We need to capture the matching string.
        $match = preg_match($this->captureRegex, $value, $matches);
        return $match === false || !isset($matches[1]) ? $value : env($matches[1]);
    }

    /**
     * @param array $server
     *
     * @return array
     */
    public function getServerOptions(array $server): array
    {
        if ($server['driver'] === 'sqlite') {
            $server['directory'] = $this->getOptionValue($server['directory']);
            return $server;
        }

        $server['host'] = $this->getOptionValue($server['host']);
        $server['username'] = $this->getOptionValue($server['username']);
        $server['password'] = $this->getOptionValue($server['password']);
        if (isset($server['port']) && is_string($server['port'])) {
            $server['port'] = $this->getOptionValue($server['port']);
        }
        return $server;
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
        // Callback to filter the servers list on valid entries.
        $check = fn(array $server) => $this->checkServer($server);
        // Callback to get the server options final values.
        $convert = fn(array $server) => $this->getServerOptions($server);
        $values['servers'] = array_map($convert,
            array_filter($values['servers'] ?? [], $check));

        return $values;
    }

    /**
     * Get the options for the authenticated user.
     *
     * @param string $configFile
     * @param array $defaultOptions
     *
     * @return array
     */
    public function getOptions(string $configFile, array $defaultOptions = []): array
    {
        // If the config file doesn't exists, return an empty array.
        if (!is_file($configFile)) {
            return [];
        }

        // The key to use for the user options
        $userKey = 'user';
        // Remove the provider field.
        unset($defaultOptions['provider']);

        $setter = new ConfigSetter();
        $reader = new ConfigReader($setter);
        $userConfig = $setter->newConfig([$userKey => $defaultOptions]);

        $config = $reader->load($setter->newConfig(), $configFile);
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
            // Return nothing if no entry is found for the user.
            return [];
        }

        // Remove the id field.
        unset($userOptions['id']);
        $userConfig = $setter->setOptions($userConfig, $userOptions, $userKey);

        return $this->getOptionValues($userConfig->getOption($userKey));
    }
}
