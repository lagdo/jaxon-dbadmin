<?php

namespace Lagdo\DbAdmin\Support\Driver;

/**
 * The database the app is currently connected to.
 */
class CurrentDbDto
{
    /**
     * @var string
     */
    public string $server = '';

    /**
     * @var string
     */
    public string $name = '';

    /**
     * @var string
     */
    public string $schema = '';
}
