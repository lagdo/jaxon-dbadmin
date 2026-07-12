<?php

namespace Lagdo\DbAdmin\Support\Provider\Config;

use Jaxon\Config\Config;

use function is_int;
use function is_string;
use function preg_match;

trait ConfigProviderTrait
{
    /**
     * @var string
     */
    private string $captureRegex = '/^env\((.*)\)$/';

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @param Config $config
     *
     * @return static
     */
    public function with(Config $config): static
    {
        $this->config = $config;
        return $this;
    }

    /**
     * @return Config
     */
    protected function config(): Config
    {
        return $this->config;
    }

    /**
     * @param string $prefix
     * @param string $option
     * @param string|int $default
     *
     * @return mixed
     */
    private function getOptionValue(string $prefix, string $option, string|int $default = ''): mixed
    {
        $value = $this->config->getOption("$prefix.{$option}", $default);
        if (is_int($value) && $option === 'port') {
            return $value;
        }
        if (!is_string($value) || $value === $default) {
            return $default;
        }

        // We need to capture the matching string.
        $match = preg_match($this->captureRegex, $value, $matches);
        return $match === false || !isset($matches[1]) ? $value : env($matches[1]);
    }
}
