<?php

namespace Lagdo\DbAdmin\Support\Provider\Secret;

use Lagdo\DbAdmin\Support\Provider\Config\SecretConfigProvider;
use Closure;

abstract class AbstractConfigProvider extends SecretConfigProvider
{
    /**
     * @var Closure
     */
    private Closure $secretKeyBuilder;

    /**
     * @param Closure $secretKeyBuilder
     *
     * @return static
     */
    public function setSecretKeyBuilder(Closure $secretKeyBuilder): static
    {
        $this->secretKeyBuilder = $secretKeyBuilder;
        return $this;
    }

    /**
     * @param string $prefix
     * @param string $option
     *
     * @return string
     */
    protected function getSecretKey(string $prefix, string $option = ''): string
    {
        return ($this->secretKeyBuilder)($prefix, $option);
    }
}
