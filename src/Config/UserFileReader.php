<?php

namespace Lagdo\DbAdmin\Db\Config;

use Jaxon\Config\ConfigReader;
use Jaxon\Config\ConfigSetter;

use function array_filter;
use function array_values;
use function in_array;
use function is_array;
use function is_file;
use function is_string;

class UserFileReader
{
    /**
     * @var string
     */
    private string $configFile;

    /**
     * The constructor
     *
     * @param AuthInterface $auth
     */
    public function __construct(private AuthInterface $auth)
    {}

    /**
     * @param string $configFile
     *
     * @return self
     */
    public function config(string $configFile): self
    {
        $this->configFile = $configFile;
        return $this;
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
     * Get the options for the authenticated user.
     *
     * @param array $defaultOptions
     *
     * @return array
     */
    public function getOptions(array $defaultOptions = []): array
    {
        // If the config file doesn't exists, return an empty array.
        if (!is_file($this->configFile)) {
            return [];
        }

        // The key to use for the user options
        $userKey = 'user';
        // Remove the provider field.
        unset($defaultOptions['provider']);

        $setter = new ConfigSetter();
        $reader = new ConfigReader($setter);
        $userConfig = $setter->newConfig([$userKey => $defaultOptions]);

        $config = $reader->load($setter->newConfig(), $this->configFile);
        $commonOptions = $config->getOption('common', null);
        if (is_array($commonOptions)) {
            $userConfig = $setter->setOptions($userConfig, $commonOptions, $userKey);
        }

        $fallbackOptions = $config->getOption('fallback', null);

        $userList = $config->getOption('users', []);
        $userList = array_values(array_filter($userList, $this->userMatches(...)));
        $userOptions = $userList[0] ?? $fallbackOptions;

        if (!is_array($userOptions)) {
            // Return nothing if no entry is found for the user.
            return [];
        }

        // Remove the id field.
        unset($userOptions['id']);
        $userConfig = $setter->setOptions($userConfig, $userOptions, $userKey);

        return $userConfig->getOption($userKey);
    }
}
