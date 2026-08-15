<?php

namespace Lagdo\DbAdmin\Support\Provider\Secret;

interface KeyBuilderInterface
{
    /**
     * @param string $prefix
     * @param string $option
     *
     * @return string
     */
    public function build(string $prefix, string $option = ''): string;
}
