<?php

namespace Lagdo\DbAdmin\Support\Provider\Config;

class SecretConfigProvider
{
    use ConfigProviderTrait;

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
    public function getCredentials(string $prefix): array
    {
        $username = $this->getUsername($prefix);
        $password = $this->getPassword($prefix);

        return [
            ...($username !== '' ? ['username' => $username] : []),
            ...($password !== '' ? ['password' => $password] : []),
        ];
    }
}
